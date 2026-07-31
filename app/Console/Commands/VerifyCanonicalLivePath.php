<?php

namespace App\Console\Commands;

use App\Models\CanonicalCurrentHead;
use App\Models\CanonicalParameter;
use App\Models\CanonicalUnit;
use App\Models\CanonicalValue;
use App\Models\DataLogger;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use App\Models\User;
use App\Services\Ingestion\CanonicalIngressService;
use App\Services\Ingestion\IngressResult;
use App\Services\Ingestion\IngressRolloutService;
use App\Services\Ingestion\IngressSubmission;
use App\Services\Ingestion\RawEventConflictException;
use App\Services\Ingestion\RawEventEnvelope;
use App\Services\Mapping\MappingActivationService;
use App\Services\Mapping\MappingProfileService;
use App\Services\Replay\CanonicalReplayService;
use Carbon\CarbonImmutable;
use Database\Seeders\CanonicalCatalogSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class VerifyCanonicalLivePath extends Command
{
    protected $signature = 'canonical:verify-live-path';

    protected $description = 'Verify raw-to-canonical lineage, idempotency, current-head ordering, and late/non-value projection safety';

    public function __construct(
        private readonly MappingProfileService $profiles,
        private readonly MappingActivationService $activations,
        private readonly CanonicalIngressService $ingress,
        private readonly CanonicalReplayService $replay,
        private readonly IngressRolloutService $rollout,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (CanonicalParameter::query()->count() === 0) {
            $this->call('db:seed', ['--class' => CanonicalCatalogSeeder::class, '--force' => true]);
        }
        $fixtureCounts = $this->fixtureCounts();
        DB::beginTransaction();
        try {
            [$actor, $project, $workspace, $station, $logger, $sensor] = $this->fixtures();
            $version = $this->publishedMapping($actor->id, $sensor);
            $this->activations->activate('sensor', $sensor->id, $version, 'Live path verification', $actor->id);
            $this->rollout->transition('http_callback', 'shadow', $actor->id, 'Live path verification shadow');
            $this->rollout->recordVerificationAttestation(
                'http_callback',
                1,
                0,
                hash('sha256', 'canonical-live-path:http_callback'),
                $actor->id,
            );
            $this->rollout->transition('http_callback', 'verified', $actor->id, 'Live path verification attested');
            $this->rollout->transition('http_callback', 'cutover', $actor->id, 'Live path verification cutover');

            $first = $this->submit($project, $station, $logger, $sensor, 'live-1', '2026-07-31T10:00:00+07:00', 302);
            $firstEvent = $first->capture->event;
            $this->check('first raw item produces traceable canonical value', $first->canonical?->projectableValue?->value_decimal === '30.20' && $first->canonical?->values->first()?->raw_ingestion_item_id === $firstEvent->items()->first()->id);
            $this->check('shared ingress result preserves stable response correlation', $first->pathKey === 'http_callback' && $first->projected && $first->projectedCanonicalValueId === $first->canonical?->projectableValue?->id && $firstEvent->logical_event_key === 'live-1');
            $this->check('exact request payload and hash remain immutable lineage', $firstEvent->payload === $this->exactPayload('live-1', 302) && $firstEvent->payload_hash === hash('sha256', $this->exactPayload('live-1', 302)));
            $valueCount = CanonicalValue::query()->count();
            $sensorState = $sensor->fresh()->getAttributes();
            $readingState = TelemetryReading::query()->firstOrFail()->getAttributes();
            $again = $this->submit($project, $station, $logger, $sensor, 'live-1', '2026-07-31T10:00:00+07:00', 302);
            $this->check('same event retry reuses raw evidence without processing or projection refresh', $again->capture->idempotent && $again->canonical === null && ! $again->projected && CanonicalValue::query()->count() === $valueCount && $sensor->fresh()->getAttributes() === $sensorState && TelemetryReading::query()->firstOrFail()->getAttributes() === $readingState);
            $this->check('same source event key with different evidence conflicts explicitly', $this->conflicts(fn () => $this->submit($project, $station, $logger, $sensor, 'live-1', '2026-07-31T10:00:00+07:00', 303)));

            $late = $this->submit($project, $station, $logger, $sensor, 'live-late', '2026-07-31T09:00:00+07:00', 350);
            $this->check('late event is retained but cannot replace/supersede current head or projection', $late->canonical?->values->first()?->value_decimal === '35.00' && $late->canonical?->values->first()?->supersedes_id === null && $late->canonical?->projectableValue === null && ! $late->projected && $this->headValue() === '30.20' && $sensor->fresh()->value === '30.20');

            $new = $this->submit($project, $station, $logger, $sensor, 'live-new', '2026-07-31T11:00:00+07:00', 320);
            $this->check('newer observation advances deterministic head and compatibility projection', $new->canonical?->projectableValue?->value_decimal === '32.00' && $new->projected && $this->headValue() === '32.00' && $sensor->fresh()->value === '32.00');

            $missing = $this->submit($project, $station, $logger, $sensor, 'live-missing', '2026-07-31T12:00:00+07:00', 65535);
            $this->check('missing outcome is immutable history and never zero/current', $missing->canonical?->values->first()?->status === 'missing' && $missing->canonical?->values->first()?->value_decimal === null && $missing->canonical?->projectableValue === null && ! $missing->projected && $this->headValue() === '32.00' && $sensor->fresh()->value === '32.00');

            $sample = CanonicalValue::query()->where('status', 'value')->firstOrFail();
            $this->check('lineage stores run/raw/rule/version/catalog and three times', $sample->canonical_processing_run_id && $sample->raw_ingestion_event_id && $sample->raw_ingestion_item_id && $sample->mapping_rule_id && $sample->mapping_profile_version_id && $sample->canonical_parameter_version_id && $sample->observed_at && $sample->received_at && $sample->processed_at);
            $this->check('all canonical outcomes are append-only revisions', CanonicalValue::query()->count() === 4 && CanonicalCurrentHead::query()->count() === 1);

            $liveSensorState = $sensor->fresh()->getAttributes();
            $liveTelemetryState = TelemetryReading::query()->firstOrFail()->getAttributes();
            $sameBatch = $this->replay->create([
                'source_type' => 'sensor', 'source_id' => $sensor->id,
                'observed_from' => '2026-07-31T00:00:00Z', 'observed_to' => '2026-07-31T23:59:59Z',
                'mapping_profile_version_id' => $version->id, 'reason' => 'Same version no-op rehearsal', 'max_events' => 100,
            ], $actor->id);
            $sameSummary = $this->replay->dryRun($sameBatch);
            $sameBatch = $this->replay->execute($sameBatch);
            $this->check('same-version replay is an explainable no-op', $sameSummary['selected'] === 4 && $sameSummary['unchanged'] === 4 && $sameBatch->unchanged_count === 4 && CanonicalValue::query()->count() === 4);

            $draftV2 = $this->profiles->clonePublished($version, 'Correct scale', $actor->id);
            $ruleV2 = $draftV2->rules()->firstOrFail();
            $this->profiles->saveRule($draftV2, ['scale' => '0.2'], $ruleV2);
            $versionV2 = $this->profiles->publish($draftV2, 'Correct scale to 0.2', $actor->id);
            $correctedBatch = $this->replay->create([
                'source_type' => 'sensor', 'source_id' => $sensor->id,
                'observed_from' => '2026-07-31T00:00:00Z', 'observed_to' => '2026-07-31T23:59:59Z',
                'mapping_profile_version_id' => $versionV2->id, 'reason' => 'Corrected version rehearsal', 'max_events' => 100,
            ], $actor->id);
            $correctedSummary = $this->replay->dryRun($correctedBatch);
            $paused = $this->replay->execute($correctedBatch, 2);
            $this->check('interrupted replay checkpoints and pauses', $correctedSummary['pending'] === 4 && $paused->status === 'paused' && $paused->corrected_count === 2);
            $resumed = $this->replay->execute($paused);
            $this->check('resume completes corrected append with stable counts/head', $resumed->status === 'completed' && $resumed->corrected_count === 4 && CanonicalValue::query()->count() === 8 && $this->headValue() === '64.00');
            $this->check('replay leaves legacy sensor/telemetry state untouched', $sensor->fresh()->getAttributes() === $liveSensorState && TelemetryReading::query()->count() === 1 && TelemetryReading::query()->firstOrFail()->getAttributes() === $liveTelemetryState);

        } catch (Throwable $exception) {
            $this->error($exception::class.': '.$exception->getMessage());
            $this->failed++;
        } finally {
            DB::rollBack();
        }

        $this->check('transactional verifier leaves fixture tables unchanged', $this->fixtureCounts() === $fixtureCounts);
        $this->newLine();
        $this->info("Canonical live-path verification: {$this->passed} passed, {$this->failed} failed. Transaction was rolled back.");

        return $this->failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private int $passed = 0;

    private int $failed = 0;

    private function check(string $label, bool $passed): void
    {
        $this->line(($passed ? '<info>PASS</info> ' : '<error>FAIL</error> ').$label);
        $passed ? $this->passed++ : $this->failed++;
    }

    private function fixtures(): array
    {
        $suffix = Str::lower(Str::random(8));
        $actor = User::query()->create([
            'name' => 'Live Path Verifier',
            'email' => "live-path-{$suffix}@example.test",
            'password' => Str::random(40),
            'dob' => '2000-01-01',
            'avatar' => '',
            'email_verified_at' => now(),
        ]);
        $project = Project::query()->create(['project_code' => 'LIVE-'.$suffix, 'name' => 'Live Verify', 'status' => 'Active']);
        $workspace = GeospatialWorkspace::query()->create(['project_id' => $project->id, 'workspace_code' => 'LW-'.$suffix, 'name' => 'Live', 'province' => 'DKI Jakarta', 'status' => 'Normal']);
        $station = MonitoringStation::query()->create(['workspace_id' => $workspace->id, 'station_code' => 'LM-'.$suffix, 'name' => 'Live Station']);
        $logger = DataLogger::query()->create(['monitoring_station_id' => $station->id, 'logger_code' => 'LD-'.$suffix, 'logger_model' => 'WX-100', 'vendor' => 'Verifier', 'logger_status' => 'Active']);
        $sensor = Sensor::query()->create(['workspace_id' => $workspace->id, 'monitoring_station_id' => $station->id, 'sensor_code' => 'LS-'.$suffix, 'type' => 'Temperature', 'parameter' => 'Air_temp_raw', 'status' => 'Normal']);

        return [$actor, $project, $workspace, $station, $logger, $sensor];
    }

    private function publishedMapping(int $actorId, Sensor $sensor)
    {
        $draft = $this->profiles->createDraft(['name' => 'Live WX', 'manufacturer' => 'Verifier', 'device_model' => 'WX-100'], $actorId);
        $unit = CanonicalUnit::query()->where('code', 'celsius')->firstOrFail();
        $parameter = CanonicalParameter::query()->where('key', 'air_temperature')->with('definition')->firstOrFail();
        $this->profiles->saveRule($draft, [
            'sort_order' => 1, 'source_parameter' => $sensor->parameter, 'source_item_key' => 'register:0',
            'parser' => 'uint16', 'byte_offset' => 0, 'byte_length' => 2, 'register_start' => 0, 'register_count' => 1,
            'signedness' => 'unsigned', 'byte_order' => 'big', 'word_order' => 'high_low', 'scale' => '0.1', 'offset' => '0',
            'source_unit_id' => $unit->id, 'canonical_parameter_id' => $parameter->id, 'canonical_parameter_version_id' => $parameter->definition->id,
            'missing_markers' => ['hex:ffff'], 'origin' => 'RDM',
        ]);

        return $this->profiles->publish($draft, 'Live path approved', $actorId);
    }

    private function submit(Project $project, MonitoringStation $station, DataLogger $logger, Sensor $sensor, string $key, string $observedAt, int $register): IngressResult
    {
        $envelope = new RawEventEnvelope(
            sourceType: 'data_logger', sourceId: $logger->id, logicalEventKey: $key, transport: 'http', payloadClassification: 'raw',
            exactPayload: $this->exactPayload($key, $register),
            sourceSnapshot: ['project' => $project->project_code, 'logger' => $logger->logger_code, 'sensor' => $sensor->sensor_code],
            projectId: $project->id, monitoringStationId: $station->id, dataLoggerId: $logger->id, sensorId: $sensor->id,
            observedAt: CarbonImmutable::parse($observedAt)->utc(), observedAtProvenance: 'device',
            items: [[
                'item_key' => 'register:0', 'source_parameter' => $sensor->parameter, 'raw_value' => (string) $register,
                'raw_bytes' => pack('n', $register), 'register_address' => 0, 'register_count' => 1,
            ]],
        );

        return $this->ingress->ingest(new IngressSubmission(
            pathKey: 'http_callback',
            envelope: $envelope,
            sensor: $sensor,
            compatibilityCandidate: ['value' => (string) $register],
        ));
    }

    private function exactPayload(string $key, int $register): string
    {
        return json_encode(['event_id' => $key, 'registers' => [$register]], JSON_THROW_ON_ERROR);
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

    private function fixtureCounts(): array
    {
        return [
            'projects' => Project::query()->count(),
            'workspaces' => GeospatialWorkspace::query()->count(),
            'stations' => MonitoringStation::query()->count(),
            'loggers' => DataLogger::query()->count(),
            'sensors' => Sensor::query()->count(),
            'telemetry' => TelemetryReading::query()->count(),
            'canonical_values' => CanonicalValue::query()->count(),
            'canonical_heads' => CanonicalCurrentHead::query()->count(),
        ];
    }

    private function headValue(): ?string
    {
        return CanonicalCurrentHead::query()->with('value')->first()?->value?->value_decimal;
    }
}
