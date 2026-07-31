<?php

namespace App\Console\Commands;

use App\Models\CanonicalCurrentHead;
use App\Models\CanonicalParameter;
use App\Models\CanonicalParameterVersion;
use App\Models\CanonicalUnit;
use App\Models\CanonicalValue;
use App\Models\DataLogger;
use App\Models\DeviceCredential;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use App\Models\User;
use App\Services\Canonicalization\CanonicalCurrentQueryService;
use App\Services\Ingestion\AuthenticatedIngressRejectionRecorder;
use App\Services\Ingestion\CanonicalIngressService;
use App\Services\Ingestion\DeviceIngressAuthenticationException;
use App\Services\Ingestion\DeviceIngressAuthenticator;
use App\Services\Ingestion\IngressRolloutService;
use App\Services\Ingestion\IngressSubmission;
use App\Services\Ingestion\RawEventConflictException;
use App\Services\Ingestion\RawEventEnvelope;
use App\Services\Mapping\MappingActivationService;
use App\Services\Mapping\MappingProfileService;
use Carbon\CarbonImmutable;
use Database\Seeders\CanonicalCatalogSeeder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyIngressConvergence extends Command
{
    protected $signature = 'canonical:verify-ingress-convergence
        {--path= : Trusted ingress path to verify; omit to run all six paths}
        {--attest : Persist bounded per-path attestations after fixture rollback}
        {--actor= : Existing verified user ID authorizing attestations}
        {--assert-operator-http : Exercise authenticated rollout routes through the Laravel HTTP kernel}
        {--assert-consumer-cutover : Exercise typed path-aware current reads, rollback fallback, and recutover}
        {--assert-normal-mode-persists= : Expected durable row delta before attestation}
        {--assert-attestations-added= : Expected attestation row delta}';

    protected $description = 'Verify rollback-isolated six-path ingress convergence and optional post-rollback attestations';

    private int $passed = 0;

    private int $failed = 0;

    private string $currentPath = 'global';

    private array $pathResults = [];

    public function __construct(
        private readonly MappingProfileService $profiles,
        private readonly MappingActivationService $activations,
        private readonly CanonicalIngressService $ingress,
        private readonly IngressRolloutService $rollout,
        private readonly CanonicalCurrentQueryService $currentReadings,
        private readonly DeviceIngressAuthenticator $authenticator,
        private readonly AuthenticatedIngressRejectionRecorder $rejections,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $paths = $this->selectedPaths();
        $before = $this->durableSnapshot();
        $attestationsBefore = DB::table('ingress_verification_attestations')->count();

        DB::beginTransaction();

        try {
            (new CanonicalCatalogSeeder)->run();
            $fixture = $this->fixture();

            foreach ($paths as $index => $path) {
                $this->currentPath = $path;
                $this->verifyPath($path, $fixture, $index);
            }

            $this->currentPath = 'global';
            $this->verifyAuthenticationBoundary($fixture, $paths);
            if (in_array('mqtt', $paths, true)) {
                $this->verifyMqttBridgeControllerPayload($fixture);
            }
            if ($this->option('assert-operator-http')) {
                $this->verifyOperatorHttpBoundary($fixture, $paths[0]);
            }
            if ($this->option('assert-consumer-cutover')) {
                $this->verifyConsumerCutover($fixture, $paths);
            }
        } catch (Throwable $exception) {
            $this->check('fixture execution completes', false);
            $this->error($exception::class.': '.$exception->getMessage());
        } finally {
            DB::rollBack();
        }

        $afterRollback = $this->durableSnapshot();
        $this->check('transactional fixtures leave every tracked table and rollout state unchanged', $afterRollback === $before);

        $expectedNormalDelta = $this->option('assert-normal-mode-persists');
        if ($expectedNormalDelta !== null) {
            $this->check(
                'normal-mode durable row delta matches the requested assertion',
                $this->totalRows($afterRollback) - $this->totalRows($before) === (int) $expectedNormalDelta,
            );
        }

        $digests = [];
        foreach ($paths as $path) {
            $digests[$path] = $this->pathDigest($path);
        }

        if ($this->option('attest')) {
            $this->persistAttestations($paths, $digests);
        }

        $expectedAttestations = $this->option('assert-attestations-added');
        if ($expectedAttestations !== null) {
            $actual = DB::table('ingress_verification_attestations')->count() - $attestationsBefore;
            $this->check('attestation row delta matches the requested assertion', $actual === (int) $expectedAttestations);
        }

        $this->newLine();
        $this->info("Ingress convergence verification {$this->rollout->verificationSuiteVersion()}: {$this->passed} passed, {$this->failed} failed. Fixture transaction was rolled back.");
        foreach ($digests as $path => $digest) {
            $this->line("{$path}: {$digest}");
        }

        return $this->failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function selectedPaths(): array
    {
        $path = trim((string) $this->option('path'));
        $paths = $path === '' ? config('canonical.ingress_rollout.paths', []) : [$path];

        foreach ($paths as $candidate) {
            $this->rollout->state($candidate);
        }

        return array_values($paths);
    }

    private function verifyPath(string $path, array $fixture, int $pathIndex): void
    {
        $actor = $fixture['actor'];
        $this->check('expand cannot skip directly to verified', $this->rejects(fn () => $this->rollout->transition($path, 'verified', $actor->id, 'Forbidden skip')));
        $this->rollout->transition($path, 'shadow', $actor->id, 'Begin transactional shadow proof');
        $this->check('shadow retains legacy compatibility and disables canonical reads', $this->rollout->compatibilityMode($path) === 'legacy' && ! $this->rollout->canonicalReadEnabled($path));
        $this->rollout->transition($path, 'rolled_back', $actor->id, 'Exercise non-destructive rollback');
        $this->check('rollback preserves legacy compatibility and disables canonical reads', $this->rollout->compatibilityMode($path) === 'legacy' && ! $this->rollout->canonicalReadEnabled($path));
        $this->rollout->transition($path, 'shadow', $actor->id, 'Resume shadow proof');

        $base = CarbonImmutable::parse('2026-07-31T00:00:00Z')->addHours($pathIndex * 2);
        $raw = $this->rawPath($path);
        $first = $this->submit($path, $fixture, "{$path}-accepted", $base, $raw ? '302' : '30.2', $raw ? 302 : null, '30.20');
        $event = $first->capture->event;
        $item = $event->items->first();
        $this->check('accepted event preserves trusted path and classification', $this->rollout->resolvePath($event) === $path && $event->payload_classification === ($raw ? 'raw' : 'pre_normalized'));
        $this->check('strongest transport evidence is retained exactly', $raw
            ? $item?->getRawOriginal('raw_bytes') === pack('n', 302) && $item?->register_address === 17
            : $item?->raw_value === '30.2' && $item?->getRawOriginal('raw_bytes') === null);
        $this->check('trusted semantics transform exactly once with a stable rounded value', $first->canonical?->projectableValue?->value_decimal === '30.20');
        $this->check('shadow projects the compatibility candidate and records parity match', $first->projected && $first->projectedValue === '30.20' && DB::table('ingress_rollout_evidence')->where('raw_ingestion_event_id', $event->id)->where('parity_status', 'match')->exists());

        $retry = $this->ingress->ingest($this->submission($path, $fixture, "{$path}-accepted", $base, $raw ? '302' : '30.2', $raw ? 302 : null, '30.20'));
        $this->check('stable retry identity is idempotent without duplicate raw/canonical state', $retry->capture->idempotent && $retry->capture->event->id === $event->id && $retry->canonical === null && ! $retry->projected);
        $this->check('same identity with changed evidence records conflict', $this->conflicts(fn () => $this->submit($path, $fixture, "{$path}-accepted", $base, $raw ? '303' : '30.3', $raw ? 303 : null, '30.30')));

        $zero = $this->submit($path, $fixture, "{$path}-zero", $base->addMinutes(10), '0', $raw ? 0 : null, '0');
        $this->check('numeric zero remains a present canonical value', $zero->canonical?->projectableValue?->value_decimal === '0.00' && $zero->projectedValue === '0');

        $missing = $this->submit($path, $fixture, "{$path}-missing", $base->addMinutes(20), '', $raw ? 65535 : null, null);
        $this->check('missing remains an explicit non-value and never projects prior state', $missing->canonical?->values->first()?->status === 'missing' && $missing->canonical?->projectableValue === null && ! $missing->projected);

        $invalid = $this->submit($path, $fixture, "{$path}-invalid", $base->addMinutes(30), 'broken', null, null, true);
        $this->check('malformed semantic input is retained as invalid', $invalid->canonical?->values->first()?->status === 'invalid' && ! $invalid->projected);
        $nonFinite = $this->submit($path, $fixture, "{$path}-non-finite", $base->addMinutes(40), 'NaN', null, null, true);
        $this->check('non-finite semantic input fails closed', $nonFinite->canonical?->values->first()?->status === 'non_finite');
        $overflow = $this->submit($path, $fixture, "{$path}-overflow", $base->addMinutes(50), str_repeat('9', 81), null, null, true);
        $this->check('overflow semantic input fails closed', $overflow->canonical?->values->first()?->status === 'overflow');
        $rounded = $this->submit($path, $fixture, "{$path}-rounding", $base->addMinutes(60), '30.205', null, '30.20', true);
        $this->check('rounding and parity difference are deterministic', $rounded->canonical?->projectableValue?->value_decimal === '30.21' && DB::table('ingress_rollout_evidence')->where('raw_ingestion_event_id', $rounded->capture->event->id)->where('parity_status', 'difference')->exists());

        $outcomes = DB::table('ingress_rollout_evidence')->where('path_key', $path)->pluck('capture_outcome');
        $this->check('bounded path evidence covers accepted, idempotent, and conflict outcomes', collect(['accepted', 'idempotent', 'conflict'])->every(fn (string $outcome) => $outcomes->contains($outcome)));
    }

    private function verifyAuthenticationBoundary(array $fixture, array $paths): void
    {
        $token = 'verify-'.Str::random(48);
        DeviceCredential::query()->create([
            'data_logger_id' => $fixture['logger']->id,
            'credential_code' => 'VERIFY-'.Str::upper(Str::random(8)),
            'device_token' => $token,
            'credential_status' => 'Active',
        ]);
        $evidenceBefore = DB::table('ingress_rollout_evidence')->count();
        $badRequest = Request::create('/api/realtime-sensor-status', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer incorrect-token',
            'CONTENT_TYPE' => 'application/json',
        ], '{}');
        $this->check('unauthenticated attempts create no raw or rollout rejection evidence', $this->rejectsAuthentication(fn () => $this->authenticator->authenticate($badRequest)) && DB::table('ingress_rollout_evidence')->count() === $evidenceBefore);

        $request = Request::create('/api/realtime-sensor-status', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
        ], str_repeat('x', (int) config('canonical.ingestion.max_payload_bytes') + 1));
        $authenticated = $this->authenticator->authenticate($request);
        $this->check(
            'authenticated credential cannot select an unconfigured rollout path',
            $this->rejectsAuthentication(fn () => $this->authenticator->resolveRealtimePath($authenticated, 'mqtt')),
        );
        $mutationBefore = $this->operationalSnapshot();

        foreach ($paths as $path) {
            $this->rejections->record($path, $authenticated, $request, 'payload_too_large', 413);
        }

        $this->check('one-over authenticated rejections add only bounded evidence', $this->operationalSnapshot() === $mutationBefore && DB::table('ingress_rollout_evidence')->count() === $evidenceBefore + count($paths));
        $row = DB::table('ingress_rollout_evidence')->latest('id')->first();
        $this->check('pre-envelope rejection stores only size, digest, status reason, path, and time facts', $row?->raw_ingestion_event_id === null && $row?->canonical_processing_run_id === null && $row?->payload_size === strlen($request->getContent()) && $row?->payload_sha256 === hash('sha256', $request->getContent()));

        $scopeEvidence = DB::table('ingress_rollout_evidence')->count();
        $this->check('cross-source resolution fails before rollout rejection recording', $this->rejectsAuthentication(fn () => $this->authenticator->resolveSensor($authenticated, $fixture['sensor']->id, null, $fixture['logger']->id + 1000)) && DB::table('ingress_rollout_evidence')->count() === $scopeEvidence);

        $maximum = (int) config('canonical.ingestion.max_payload_bytes');
        $this->check('exact payload limit forms an envelope while one-over fails before capture', $this->acceptsEnvelope($fixture, str_repeat('x', $maximum)) && ! $this->acceptsEnvelope($fixture, str_repeat('x', $maximum + 1)));
    }

    private function verifyMqttBridgeControllerPayload(array $fixture): void
    {
        $token = 'verify-mqtt-'.Str::random(40);
        DeviceCredential::query()->create([
            'data_logger_id' => $fixture['logger']->id,
            'credential_code' => 'VERIFY-MQTT-'.Str::upper(Str::random(8)),
            'device_token' => $token,
            'mqtt_username' => 'verification-client',
            'credential_status' => 'Active',
        ]);
        $eventKey = 'mqtt-bridge-'.Str::lower(Str::random(12));
        $rawPayload = json_encode(['sensor_code' => $fixture['sensor']->sensor_code, 'value' => 30.2], JSON_THROW_ON_ERROR);
        $payload = json_encode([
            'event_id' => $eventKey,
            'envelope_version' => '1',
            'transport' => 'mqtt',
            'payload_classification' => 'pre_normalized',
            'observed_at' => '2026-07-31T18:00:00.000Z',
            'sensor_id' => $fixture['sensor']->id,
            'data_logger_id' => $fixture['logger']->id,
            'raw_payload' => $rawPayload,
            'value' => '30.2',
        ], JSON_THROW_ON_ERROR);
        $request = Request::create('/api/realtime-sensor-status', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
        $kernel = app(HttpKernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $event = DB::table('raw_ingestion_events')->where('logical_event_key', $eventKey)->first();
        $items = $event
            ? DB::table('raw_ingestion_items')->where('raw_ingestion_event_id', $event->id)->orderBy('id')->get()
            : collect();
        $semantic = $items->firstWhere('item_key', 'register:0');
        $evidence = $items->firstWhere('item_key', 'raw_payload');
        $body = json_decode((string) $response->getContent(), true);

        $this->check(
            'actual MQTT bridge payload preserves raw evidence and maps the trusted extracted value',
            $response->getStatusCode() === 200
                && $event?->payload === $payload
                && $semantic?->raw_value === '30.2'
                && $evidence?->raw_value === $rawPayload
                && ($body['canonical']['mapped'] ?? false) === true
                && ! empty($body['canonical']['projected_value_id']),
        );
    }

    private function verifyOperatorHttpBoundary(array $fixture, string $path): void
    {
        $actor = $fixture['actor'];
        $deniedActor = User::query()->forceCreate([
            'name' => 'Denied Ingress Operator',
            'email' => 'denied-ingress-'.Str::lower(Str::random(8)).'@example.test',
            'password' => Hash::make(Str::random(32)),
            'dob' => '1990-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'email_verified_at' => null,
        ]);
        $before = $this->rolloutMutationSnapshot($path);

        $authorizedGet = $this->applicationRequest('GET', '/canonical-ingress-rollout?path='.$path, $actor);
        $this->check('authorized verified session can inspect bounded rollout evidence through the HTTP kernel', $authorizedGet->getStatusCode() === 200 && $this->rolloutMutationSnapshot($path) === $before);

        $deniedGet = $this->applicationRequest('GET', '/canonical-ingress-rollout?path='.$path, $deniedActor);
        $this->check('Gate-denied session cannot inspect rollout evidence and causes no mutation', $deniedGet->getStatusCode() === 403 && $this->rolloutMutationSnapshot($path) === $before);

        $unauthenticatedGet = $this->applicationRequest('GET', '/canonical-ingress-rollout?path='.$path);
        $this->check('unauthenticated session cannot inspect rollout evidence and causes no mutation', $unauthenticatedGet->isRedirect() && $this->rolloutMutationSnapshot($path) === $before);

        $transition = [
            'path' => $path,
            'target_state' => 'rolled_back',
            'reason' => 'Kernel-authenticated rollback proof',
            'actor_id' => $deniedActor->id,
        ];
        $deniedPost = $this->applicationRequest('POST', '/canonical-ingress-rollout/transition', $deniedActor, $transition);
        $this->check('Gate-denied transition is rejected without changing state or immutable audit counts', $deniedPost->getStatusCode() === 403 && $this->rolloutMutationSnapshot($path) === $before);

        $unauthenticatedPost = $this->applicationRequest('POST', '/canonical-ingress-rollout/transition', null, $transition);
        $this->check('unauthenticated transition is rejected without changing state or immutable audit counts', $unauthenticatedPost->isRedirect() && $this->rolloutMutationSnapshot($path) === $before);

        $missingCsrf = $this->applicationRequest('POST', '/canonical-ingress-rollout/transition', $actor, $transition, 'missing');
        $this->check('missing CSRF is rejected without changing state or immutable audit counts', $missingCsrf->getStatusCode() === 419 && $this->rolloutMutationSnapshot($path) === $before);

        $invalidCsrf = $this->applicationRequest('POST', '/canonical-ingress-rollout/transition', $actor, $transition, 'invalid');
        $this->check('invalid CSRF is rejected without changing state or immutable audit counts', $invalidCsrf->getStatusCode() === 419 && $this->rolloutMutationSnapshot($path) === $before);

        $forbidden = $this->applicationRequest('POST', '/canonical-ingress-rollout/transition', $actor, [
            ...$transition,
            'target_state' => 'cutover',
        ]);
        $this->check('forbidden transition edge is rejected without changing state or immutable audit counts', $forbidden->isRedirect() && $this->rolloutMutationSnapshot($path) === $before);

        $invalidReason = $this->applicationRequest('POST', '/canonical-ingress-rollout/transition', $actor, [
            ...$transition,
            'reason' => '',
        ]);
        $this->check('missing transition reason is rejected without changing state or immutable audit counts', $invalidReason->isRedirect() && $this->rolloutMutationSnapshot($path) === $before);

        $allowed = $this->applicationRequest('POST', '/canonical-ingress-rollout/transition', $actor, $transition);
        $after = $this->rolloutMutationSnapshot($path);
        $latestTransition = DB::table('ingress_rollout_transitions')->where('path_key', $path)->latest('id')->first();
        $this->check('valid CSRF transition is session-attributed and ignores a submitted actor override', $allowed->isRedirect()
            && $after['state']['state'] === 'rolled_back'
            && (int) $after['state']['actor_id'] === $actor->id
            && count($after['transition_ids']) === count($before['transition_ids']) + 1
            && (int) $latestTransition?->actor_id === $actor->id
            && $latestTransition?->reason === $transition['reason']
            && $after['evidence_ids'] === $before['evidence_ids']
            && $after['attestation_ids'] === $before['attestation_ids']);
    }

    private function applicationRequest(string $method, string $uri, ?User $user = null, array $parameters = [], string $csrf = 'valid'): Response
    {
        if (! config('app.key')) {
            config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
            app()->forgetInstance('encrypter');
        }

        Auth::forgetGuards();
        $guardName = Auth::guard()->getName();
        $session = app('session')->driver();
        $session->setId(Str::random(40));
        $session->start();
        $session->flush();
        if ($user) {
            $session->put($guardName, $user->getAuthIdentifier());
        }
        $session->regenerateToken();
        $token = $session->token();
        $session->save();

        if ($method !== 'GET' && $csrf !== 'missing') {
            $parameters['_token'] = $csrf === 'valid' ? $token : 'invalid-csrf-token';
        }

        $cookieName = $session->getName();
        $encrypter = app('encrypter');
        $cookieValue = $encrypter->encrypt(
            CookieValuePrefix::create($cookieName, $encrypter->getKey()).$session->getId(),
            EncryptCookies::serialized($cookieName),
        );
        $request = Request::create($uri, $method, $parameters, [$cookieName => $cookieValue], [], [
            'HTTP_ACCEPT' => 'text/html',
        ]);
        $kernel = app(HttpKernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        Auth::forgetGuards();

        return $response;
    }

    private function rolloutMutationSnapshot(string $path): array
    {
        return [
            'state' => (array) DB::table('ingress_rollout_states')->where('path_key', $path)->first(),
            'transition_ids' => DB::table('ingress_rollout_transitions')->where('path_key', $path)->orderBy('id')->pluck('id')->all(),
            'evidence_ids' => DB::table('ingress_rollout_evidence')->where('path_key', $path)->orderBy('id')->pluck('id')->all(),
            'attestation_ids' => DB::table('ingress_verification_attestations')->where('path_key', $path)->orderBy('id')->pluck('id')->all(),
        ];
    }

    private function verifyConsumerCutover(array $fixture, array $paths): void
    {
        if (count($paths) < 2) {
            $this->check('consumer cutover requires one enabled and one disabled trusted path', false);

            return;
        }

        $this->currentPath = 'consumer';
        [$enabledPath, $disabledPath] = array_values($paths);
        $actor = $fixture['actor'];
        $this->movePathToShadow($enabledPath, $actor->id);
        $this->rollout->recordVerificationAttestation($enabledPath, 1, 0, hash('sha256', 'consumer-initial-'.$enabledPath), $actor->id);
        $this->rollout->transition($enabledPath, 'verified', $actor->id, 'Consumer fixture verified');
        $this->rollout->transition($enabledPath, 'cutover', $actor->id, 'Enable canonical consumer fixture');

        $secondary = Sensor::query()->create([
            'workspace_id' => $fixture['workspace']->id,
            'monitoring_station_id' => $fixture['station']->id,
            'sensor_code' => 'IS-FALLBACK-'.Str::lower(Str::random(6)),
            'type' => 'Temperature',
            'parameter' => 'Air_temp_raw',
            'status' => 'Normal',
        ]);
        $this->activations->activate('sensor', $secondary->id, $fixture['version'], 'Consumer fallback mapping', $actor->id);
        $observedAt = CarbonImmutable::parse('2026-08-01T12:00:00Z');
        $primaryResult = $this->submit($enabledPath, $fixture, 'consumer-enabled-'.Str::lower(Str::random(8)), $observedAt, '412', 412, '41.20');
        $secondaryFixture = [...$fixture, 'sensor' => $secondary];
        $this->submit($disabledPath, $secondaryFixture, 'consumer-disabled-'.Str::lower(Str::random(8)), $observedAt->addMinute(), '233', 233, '23.30');

        $baseValue = $primaryResult->canonical?->projectableValue;
        if (! $baseValue) {
            $this->check('consumer fixture produces a canonical base head', false);

            return;
        }

        $zero = $this->seedTypedHead($baseValue, $fixture['sensor']->id, 'decimal', '0.0000', 'fixture_zero');
        [$text, $boolean] = $this->processTypedValues($fixture, $enabledPath, $baseValue, $observedAt->addMinutes(2));
        $this->check('production processing stores text and boolean in exactly their typed columns',
            $text?->value_text === 'Sensor <script>fixture</script>'
            && $text?->value_decimal === null
            && $text?->value_boolean === null
            && $boolean?->value_boolean === false
            && $boolean?->value_decimal === null
            && $boolean?->value_text === null);
        $nonValue = $this->seedTypedHead($baseValue, $fixture['sensor']->id, 'decimal', null, 'fixture_missing', 'missing');

        TelemetryReading::query()->create([
            'sensor_id' => $fixture['sensor']->id,
            'data_logger_id' => $fixture['logger']->id,
            'value' => 'legacy-primary-latest',
            'alert_level' => 'Normal',
            'status' => 'Normal',
            'received_at' => $observedAt->addDay(),
        ]);
        TelemetryReading::query()->create([
            'sensor_id' => $secondary->id,
            'data_logger_id' => $fixture['logger']->id,
            'value' => 'legacy-secondary-latest',
            'alert_level' => 'Normal',
            'status' => 'Normal',
            'received_at' => $observedAt->addDay(),
        ]);

        $beforeRead = $this->consumerImmutableSnapshot();
        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();
        $readings = $this->currentReadings->forSensors([
            $fixture['sensor']->id,
            $secondary->id,
            $fixture['sensor']->id,
            0,
            'invalid',
        ]);
        $queryLog = DB::connection()->getQueryLog();
        DB::connection()->disableQueryLog();
        $headQueries = collect($queryLog)->filter(fn (array $query): bool => Str::contains(strtolower($query['query']), 'canonical_current_heads'))->count();
        $primaryReadings = $readings->where('sourceId', $fixture['sensor']->id)->values();

        $this->check('one sanitized batch current-head query serves the complete sensor cohort', $headQueries === 1 && $readings->where('sourceId', $secondary->id)->isEmpty());
        $this->check('current-head filtering is mutation-free', $this->consumerImmutableSnapshot() === $beforeRead);
        $this->check('decimal precision and genuine zero remain exact strings', $primaryReadings->contains(fn ($reading) => $reading->canonicalValueId === $zero->id && $reading->valueDecimal === '0.0000'));
        $this->check('text remains a typed string without presentation interpretation', $primaryReadings->contains(fn ($reading) => $reading->canonicalValueId === $text->id && $reading->valueText === 'Sensor <script>fixture</script>'));
        $this->check('boolean false remains a present typed value', $primaryReadings->contains(fn ($reading) => $reading->canonicalValueId === $boolean->id && $reading->valueBoolean === false && $reading->value() === false));
        $this->check('non-value current heads are excluded rather than rendered as zero or prior state', ! $primaryReadings->contains(fn ($reading) => $reading->canonicalValueId === $nonValue->id));

        $cutoverResponse = $this->applicationRequest('GET', '/telemetry/live-data', $actor);
        $cutoverRows = collect(json_decode($cutoverResponse->getContent(), true, 512, JSON_THROW_ON_ERROR)['telemetryReadings'] ?? []);
        $primaryRows = $cutoverRows->where('sensor_id', $fixture['sensor']->sensor_code)->values();
        $secondaryRows = $cutoverRows->where('sensor_id', $secondary->sensor_code)->values();
        $this->check('Registered Telemetry emits canonical rows only when a sensor has eligible heads', $cutoverResponse->getStatusCode() === 200
            && $primaryRows->count() === $primaryReadings->count()
            && $primaryRows->every(fn (array $row): bool => $row['reading_source'] === 'canonical' && ! array_key_exists('db_id', $row)));
        $this->check('disabled-path sensor emits only its latest legacy row with compatible actions', $secondaryRows->count() === 1
            && $secondaryRows->first()['reading_source'] === 'legacy'
            && $secondaryRows->first()['value'] === 'legacy-secondary-latest'
            && isset($secondaryRows->first()['db_id']));

        $beforeRollback = $this->consumerImmutableSnapshot();
        $this->rollout->transition($enabledPath, 'rolled_back', $actor->id, 'Immediate consumer rollback proof');
        $this->check('rollback preserves every head and immutable evidence count byte-for-byte', $this->consumerImmutableSnapshot() === $beforeRollback);

        $rollbackResponse = $this->applicationRequest('GET', '/telemetry/live-data', $actor);
        $rollbackRows = collect(json_decode($rollbackResponse->getContent(), true, 512, JSON_THROW_ON_ERROR)['telemetryReadings'] ?? []);
        $primaryFallback = $rollbackRows->where('sensor_id', $fixture['sensor']->sensor_code)->values();
        $this->check('rollback immediately replaces canonical rows with the latest per-sensor legacy fallback', $primaryFallback->count() === 1
            && $primaryFallback->first()['reading_source'] === 'legacy'
            && $primaryFallback->first()['value'] === 'legacy-primary-latest'
            && isset($primaryFallback->first()['db_id']));

        $headIds = CanonicalCurrentHead::query()->orderBy('id')->pluck('id')->all();
        $this->rollout->transition($enabledPath, 'shadow', $actor->id, 'Resume consumer shadow');
        $this->rollout->recordVerificationAttestation($enabledPath, 1, 0, hash('sha256', 'consumer-fresh-'.$enabledPath), $actor->id);
        $this->rollout->transition($enabledPath, 'verified', $actor->id, 'Fresh consumer re-attestation');
        $this->rollout->transition($enabledPath, 'cutover', $actor->id, 'Legal consumer recutover');
        $recutover = $this->currentReadings->forSensors([$fixture['sensor']->id]);
        $this->check('fresh legal recutover exposes the same preserved heads without recreation', CanonicalCurrentHead::query()->orderBy('id')->pluck('id')->all() === $headIds
            && $recutover->pluck('headId')->sort()->values()->all() === $primaryReadings->pluck('headId')->sort()->values()->all());
    }

    private function movePathToShadow(string $path, int $actorId): void
    {
        $state = $this->rollout->state($path)->state;
        if ($state === 'expand') {
            $this->rollout->transition($path, 'shadow', $actorId, 'Enter consumer shadow fixture');
        } elseif ($state === 'rolled_back') {
            $this->rollout->transition($path, 'shadow', $actorId, 'Resume consumer shadow fixture');
        } elseif ($state === 'verified' || $state === 'cutover') {
            $this->rollout->transition($path, 'rolled_back', $actorId, 'Normalize consumer fixture');
            $this->rollout->transition($path, 'shadow', $actorId, 'Resume consumer shadow fixture');
        }
    }

    private function processTypedValues(array $fixture, string $path, CanonicalValue $baseValue, CarbonImmutable $observedAt): array
    {
        $actor = $fixture['actor'];
        $draft = $this->profiles->clonePublished($fixture['version'], 'Add typed verifier mappings', $actor->id);
        $unit = CanonicalUnit::query()->findOrFail($baseValue->canonical_unit_id);
        $definitions = [];

        foreach (['text' => 'fixture_text', 'boolean' => 'fixture_boolean'] as $dataType => $key) {
            $parameter = CanonicalParameter::query()->create([
                'key' => $key.'_'.Str::lower(Str::random(8)),
                'domain' => 'meteorology',
                'lifecycle' => 'active',
                'current_version' => 1,
            ]);
            $definition = CanonicalParameterVersion::query()->create([
                'canonical_parameter_id' => $parameter->id,
                'version' => 1,
                'display_name' => str($key)->replace('_', ' ')->title()->toString(),
                'definition' => 'Rollback-isolated production typed-value fixture.',
                'canonical_unit_id' => $unit->id,
                'data_type' => $dataType,
                'measurement_characteristic' => 'instantaneous',
                'output_precision' => 0,
                'rounding_mode' => 'half_up',
                'source_document' => 'Verifier fixture',
                'effective_at' => now(),
            ]);
            $definitions[$dataType] = $definition;
            $this->profiles->saveRule($draft, [
                'source_parameter' => $key,
                'source_item_key' => $key,
                'parser' => $dataType === 'text' ? 'text' : 'boolean',
                'byte_offset' => 0,
                'byte_length' => 1,
                'register_start' => null,
                'register_count' => null,
                'signedness' => 'not_applicable',
                'byte_order' => 'big',
                'word_order' => 'high_low',
                'scale' => '1',
                'offset' => '0',
                'source_unit_id' => $unit->id,
                'canonical_parameter_id' => $parameter->id,
                'canonical_parameter_version_id' => $definition->id,
                'missing_markers' => [],
                'origin' => 'RDM',
            ]);
        }

        $version = $this->profiles->publish($draft, 'Exercise production text and boolean persistence', $actor->id);
        $this->activations->activate('sensor', $fixture['sensor']->id, $version, 'Typed persistence verification', $actor->id);
        $eventKey = 'consumer-typed-'.Str::lower(Str::random(8));
        $payload = json_encode([
            'event_id' => $eventKey,
            'text' => 'Sensor <script>fixture</script>',
            'boolean' => false,
        ], JSON_THROW_ON_ERROR);
        $envelope = new RawEventEnvelope(
            sourceType: 'data_logger',
            sourceId: $fixture['logger']->id,
            logicalEventKey: $eventKey,
            transport: $this->transport($path),
            payloadClassification: 'pre_normalized',
            exactPayload: $payload,
            sourceSnapshot: ['ingress_path' => $path],
            projectId: $fixture['project']->id,
            monitoringStationId: $fixture['station']->id,
            dataLoggerId: $fixture['logger']->id,
            sensorId: $fixture['sensor']->id,
            observedAt: $observedAt,
            observedAtProvenance: 'device',
            items: [
                [
                    'item_key' => 'fixture_text',
                    'source_parameter' => 'fixture_text',
                    'raw_value' => 'Sensor <script>fixture</script>',
                    'metadata' => ['payload_semantics' => 'pre_normalized'],
                ],
                [
                    'item_key' => 'fixture_boolean',
                    'source_parameter' => 'fixture_boolean',
                    'raw_value' => 'false',
                    'metadata' => ['payload_semantics' => 'pre_normalized'],
                ],
            ],
        );
        $result = $this->ingress->ingest(new IngressSubmission($path, $envelope, $fixture['sensor']));

        return [
            $result->canonical?->values->firstWhere('canonical_parameter_version_id', $definitions['text']->id),
            $result->canonical?->values->firstWhere('canonical_parameter_version_id', $definitions['boolean']->id),
        ];
    }

    private function seedTypedHead(CanonicalValue $base, int $sensorId, string $dataType, string|bool|null $typedValue, string $key, string $status = 'value'): CanonicalValue
    {
        $parameter = CanonicalParameter::query()->create([
            'key' => $key.'_'.Str::lower(Str::random(8)),
            'domain' => 'meteorology',
            'lifecycle' => 'active',
            'current_version' => 1,
        ]);
        $definition = CanonicalParameterVersion::query()->create([
            'canonical_parameter_id' => $parameter->id,
            'version' => 1,
            'display_name' => str($key)->replace('_', ' ')->title()->toString(),
            'definition' => 'Rollback-isolated consumer verifier fixture.',
            'canonical_unit_id' => $base->canonical_unit_id,
            'data_type' => $dataType,
            'measurement_characteristic' => 'instantaneous',
            'output_precision' => 4,
            'rounding_mode' => 'half_up',
            'source_document' => 'Verifier fixture',
            'effective_at' => now(),
        ]);
        $value = CanonicalValue::query()->create([
            'processing_key' => hash('sha256', $key.'|'.Str::random(32)),
            'canonical_observation_id' => $base->canonical_observation_id,
            'canonical_processing_run_id' => $base->canonical_processing_run_id,
            'raw_ingestion_event_id' => $base->raw_ingestion_event_id,
            'raw_ingestion_item_id' => $base->raw_ingestion_item_id,
            'mapping_profile_version_id' => $base->mapping_profile_version_id,
            'mapping_rule_id' => $base->mapping_rule_id,
            'canonical_parameter_id' => $parameter->id,
            'canonical_parameter_version_id' => $definition->id,
            'canonical_unit_id' => $base->canonical_unit_id,
            'domain' => 'meteorology',
            'data_type' => $dataType,
            'value_decimal' => $status === 'value' && $dataType === 'decimal' ? (string) $typedValue : null,
            'value_text' => $status === 'value' && $dataType === 'text' ? (string) $typedValue : null,
            'value_boolean' => $status === 'value' && $dataType === 'boolean' ? (bool) $typedValue : null,
            'status' => $status,
            'quality' => $status === 'value' ? 'valid' : 'not_available',
            'reason' => $status === 'value' ? null : 'fixture_non_value',
            'origin' => 'RDM',
            'revision' => 1,
            'observed_at' => $base->observed_at,
            'received_at' => $base->received_at,
            'processed_at' => now(),
            'stage_trace' => ['fixture' => 'consumer_cutover'],
            'engine_version' => $base->engine_version,
            'run_mode' => $base->run_mode,
        ]);
        CanonicalCurrentHead::query()->create([
            'source_type' => 'sensor',
            'source_id' => $sensorId,
            'canonical_parameter_id' => $parameter->id,
            'canonical_value_id' => $value->id,
            'winner_observed_at' => $value->observed_at,
            'winner_mapping_version' => 1,
            'winner_revision' => 1,
        ]);

        return $value;
    }

    private function consumerImmutableSnapshot(): array
    {
        $tables = [
            'raw_ingestion_events',
            'raw_ingestion_items',
            'canonical_processing_runs',
            'canonical_values',
            'mapping_profiles',
            'mapping_profile_versions',
            'mapping_rules',
            'mapping_assignments',
            'mapping_activation_logs',
            'ingress_rollout_evidence',
            'ingress_verification_attestations',
        ];

        return [
            'head_ids' => CanonicalCurrentHead::query()->orderBy('id')->pluck('id')->all(),
            'counts' => collect($tables)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all(),
        ];
    }

    private function fixture(): array
    {
        $suffix = Str::lower(Str::random(8));
        $actor = User::query()->forceCreate([
            'name' => 'Ingress Verifier',
            'email' => "ingress-verifier-{$suffix}@example.test",
            'password' => Hash::make(Str::random(32)),
            'dob' => '1990-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'email_verified_at' => now(),
        ]);
        $project = Project::query()->create(['project_code' => 'ING-'.$suffix, 'name' => 'Ingress Verify', 'status' => 'Active']);
        $workspace = GeospatialWorkspace::query()->create(['project_id' => $project->id, 'workspace_code' => 'IW-'.$suffix, 'name' => 'Ingress', 'province' => 'DKI Jakarta', 'status' => 'Normal']);
        $station = MonitoringStation::query()->create(['workspace_id' => $workspace->id, 'station_code' => 'IM-'.$suffix, 'name' => 'Ingress Station']);
        $logger = DataLogger::query()->create(['monitoring_station_id' => $station->id, 'logger_code' => 'ID-'.$suffix, 'logger_model' => 'WX-100', 'vendor' => 'Verifier', 'logger_status' => 'Active']);
        $sensor = Sensor::query()->create(['workspace_id' => $workspace->id, 'monitoring_station_id' => $station->id, 'sensor_code' => 'IS-'.$suffix, 'type' => 'Temperature', 'parameter' => 'Air_temp_raw', 'status' => 'Normal']);

        $draft = $this->profiles->createDraft(['name' => 'Ingress WX', 'manufacturer' => 'Verifier', 'device_model' => 'WX-100'], $actor->id);
        $unit = CanonicalUnit::query()->where('code', 'celsius')->firstOrFail();
        $parameter = CanonicalParameter::query()->where('key', 'air_temperature')->with('definition')->firstOrFail();
        $this->profiles->saveRule($draft, [
            'sort_order' => 1, 'source_parameter' => $sensor->parameter, 'source_item_key' => 'register:0',
            'parser' => 'uint16', 'byte_offset' => 0, 'byte_length' => 2, 'register_start' => 17, 'register_count' => 1,
            'signedness' => 'unsigned', 'byte_order' => 'big', 'word_order' => 'high_low', 'scale' => '0.1', 'offset' => '0',
            'source_unit_id' => $unit->id, 'canonical_parameter_id' => $parameter->id, 'canonical_parameter_version_id' => $parameter->definition->id,
            'missing_markers' => ['hex:ffff'], 'origin' => 'RDM',
        ]);
        $version = $this->profiles->publish($draft, 'Ingress verification mapping', $actor->id);
        $this->activations->activate('sensor', $sensor->id, $version, 'Ingress convergence verification', $actor->id);

        return compact('actor', 'project', 'workspace', 'station', 'logger', 'sensor', 'version');
    }

    private function submit(string $path, array $fixture, string $key, CarbonImmutable $observedAt, string $semanticValue, ?int $register, ?string $compatibilityValue, bool $forcePreNormalized = false)
    {
        return $this->ingress->ingest($this->submission($path, $fixture, $key, $observedAt, $semanticValue, $register, $compatibilityValue, $forcePreNormalized));
    }

    private function submission(string $path, array $fixture, string $key, CarbonImmutable $observedAt, string $semanticValue, ?int $register, ?string $compatibilityValue, bool $forcePreNormalized = false): IngressSubmission
    {
        $raw = $register !== null && ! $forcePreNormalized;
        $payload = json_encode([
            'event_id' => $key,
            'path' => $path,
            'observed_at' => $observedAt->toISOString(),
            ...$raw ? ['registers' => [$register], 'register_address' => 17, 'function_code' => 'FC03'] : ['value' => $semanticValue],
        ], JSON_THROW_ON_ERROR);
        $sensor = $fixture['sensor'];
        $logger = $fixture['logger'];
        $envelope = new RawEventEnvelope(
            sourceType: $path === 'manual' ? 'sensor' : 'data_logger',
            sourceId: $path === 'manual' ? $sensor->id : $logger->id,
            logicalEventKey: $key,
            transport: $this->transport($path),
            payloadClassification: $raw ? 'raw' : 'pre_normalized',
            exactPayload: $payload,
            sourceSnapshot: ['ingress_path' => $path, 'logger_code' => $logger->logger_code, 'sensor_code' => $sensor->sensor_code],
            projectId: $fixture['project']->id,
            monitoringStationId: $fixture['station']->id,
            dataLoggerId: $logger->id,
            sensorId: $sensor->id,
            observedAt: $observedAt,
            observedAtProvenance: $path === 'manual' ? 'operator' : 'device',
            items: [[
                'item_key' => 'register:0',
                'source_parameter' => $sensor->parameter,
                'raw_value' => $raw ? (string) $register : $semanticValue,
                ...$raw ? ['raw_bytes' => pack('n', $register), 'register_address' => 17, 'register_count' => 1] : [],
                'status' => $semanticValue === '' ? 'missing' : 'received',
                'reason' => $semanticValue === '' ? 'source_value_absent' : null,
                'metadata' => ['payload_semantics' => $raw ? 'raw' : 'pre_normalized'],
            ]],
        );

        return new IngressSubmission($path, $envelope, $sensor, ['value' => $compatibilityValue]);
    }

    private function transport(string $path): string
    {
        return match ($path) {
            'http_callback' => 'http',
            'modbus_tcp' => 'modbus_tcp',
            'mqtt' => 'mqtt',
            'rednode_callback', 'rednode_heartbeat' => 'rednode',
            'manual' => 'manual',
        };
    }

    private function rawPath(string $path): bool
    {
        return in_array($path, ['http_callback', 'modbus_tcp', 'rednode_callback', 'rednode_heartbeat'], true);
    }

    private function acceptsEnvelope(array $fixture, string $payload): bool
    {
        try {
            new RawEventEnvelope(
                sourceType: 'data_logger', sourceId: $fixture['logger']->id, logicalEventKey: 'limit-'.strlen($payload),
                transport: 'http', payloadClassification: 'pre_normalized', exactPayload: $payload,
                sourceSnapshot: ['ingress_path' => 'http_callback'], observedAt: CarbonImmutable::now('UTC'),
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function persistAttestations(array $paths, array $digests): void
    {
        if ($this->failed !== 0) {
            $this->error('A failing fixture suite cannot be attested.');

            return;
        }

        try {
            $actor = $this->authorizedActor();
            foreach ($paths as $path) {
                $results = $this->pathResults[$path] ?? [];
                $passed = collect($results)->where('passed', true)->count();
                $failed = collect($results)->where('passed', false)->count();
                $this->rollout->recordVerificationAttestation($path, $passed, $failed, $digests[$path], (int) $actor->getKey());
            }
            $this->info('Post-rollback per-path verification attestations persisted.');
        } catch (Throwable $exception) {
            $this->failed++;
            $this->error($exception::class.': '.$exception->getMessage());
        }
    }

    private function authorizedActor(): User
    {
        $actorId = filter_var($this->option('actor'), FILTER_VALIDATE_INT);
        if (! $actorId) {
            throw new LogicException('The --actor option must identify an existing verified user.');
        }

        $actor = User::query()->findOrFail($actorId);
        Gate::forUser($actor)->authorize('manage-canonical-mappings');

        return $actor;
    }

    private function pathDigest(string $path): string
    {
        return hash('sha256', json_encode([
            'suite_version' => $this->rollout->verificationSuiteVersion(),
            'path_key' => $path,
            'results' => $this->pathResults[$path] ?? [],
        ], JSON_THROW_ON_ERROR));
    }

    private function check(string $label, bool $passed): void
    {
        $this->line(($passed ? '<info>PASS</info> ' : '<error>FAIL</error> ')."[{$this->currentPath}] {$label}");
        $result = ['label' => $label, 'passed' => $passed];
        $this->pathResults[$this->currentPath][] = $result;
        $passed ? $this->passed++ : $this->failed++;
    }

    private function conflicts(callable $operation): bool
    {
        try {
            $operation();
        } catch (RawEventConflictException) {
            return true;
        }

        return false;
    }

    private function rejects(callable $operation): bool
    {
        try {
            $operation();
        } catch (LogicException) {
            return true;
        }

        return false;
    }

    private function rejectsAuthentication(callable $operation): bool
    {
        try {
            $operation();
        } catch (DeviceIngressAuthenticationException) {
            return true;
        }

        return false;
    }

    private function operationalSnapshot(): array
    {
        return [
            'raw_events' => DB::table('raw_ingestion_events')->count(),
            'raw_items' => DB::table('raw_ingestion_items')->count(),
            'runs' => DB::table('canonical_processing_runs')->count(),
            'values' => CanonicalValue::query()->count(),
            'heads' => CanonicalCurrentHead::query()->count(),
            'sensor' => Sensor::query()->findOrFail(Sensor::query()->max('id'))->getAttributes(),
            'telemetry' => TelemetryReading::query()->orderBy('id')->get()->map->getAttributes()->all(),
        ];
    }

    private function durableSnapshot(): array
    {
        $tables = [
            'users', 'resq_projects', 'geospatial_workspaces', 'monitoring_stations', 'data_loggers', 'device_credentials',
            'sensors', 'connectivity_configs', 'telemetry_readings', 'canonical_units', 'canonical_parameters',
            'canonical_parameter_versions', 'mapping_profiles', 'mapping_profile_versions', 'mapping_rules',
            'mapping_assignments', 'mapping_activation_logs', 'raw_ingestion_events', 'raw_ingestion_items',
            'canonical_processing_runs', 'canonical_observations', 'canonical_values', 'canonical_current_heads',
            'ingress_rollout_transitions', 'ingress_rollout_evidence', 'ingress_verification_attestations',
        ];
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return [
            'counts' => $counts,
            'rollout_states' => DB::table('ingress_rollout_states')->orderBy('path_key')->get()->map(fn (object $row): array => (array) $row)->all(),
        ];
    }

    private function totalRows(array $snapshot): int
    {
        return array_sum($snapshot['counts']);
    }
}
