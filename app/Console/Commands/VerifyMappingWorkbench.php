<?php

namespace App\Console\Commands;

use App\Models\CanonicalParameter;
use App\Models\CanonicalUnit;
use App\Models\DataLogger;
use App\Models\GeospatialWorkspace;
use App\Models\MappingActivationLog;
use App\Models\MappingAssignment;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\RawIngestionEvent;
use App\Models\RawIngestionItem;
use App\Models\TelemetryReading;
use App\Models\User;
use App\Services\Mapping\MappingActivationService;
use App\Services\Mapping\MappingPreviewService;
use App\Services\Mapping\MappingProfileService;
use App\Services\Mapping\MappingValidationException;
use Database\Seeders\CanonicalCatalogSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class VerifyMappingWorkbench extends Command
{
    protected $signature = 'canonical:verify-mapping-workbench';

    protected $description = 'Run transactional database integration checks for mapping authoring, preview, publish, activation, and rollback';

    public function __construct(
        private readonly MappingProfileService $profiles,
        private readonly MappingPreviewService $preview,
        private readonly MappingActivationService $activations,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (CanonicalParameter::query()->count() === 0) {
            $this->call('db:seed', ['--class' => CanonicalCatalogSeeder::class, '--force' => true]);
        }

        DB::beginTransaction();
        try {
            $actor = User::query()->first();
            if (! $actor) {
                $actor = User::query()->create([
                    'name' => 'Mapping Verifier',
                    'email' => 'mapping-verifier@example.test',
                    'password' => bcrypt(Str::random(40)),
                    'dob' => '2000-01-01',
                    'avatar' => 'images/avatar-1.jpg',
                ]);
            }
            if (! $actor->email_verified_at) {
                DB::table('users')->where('id', $actor->id)->update(['email_verified_at' => now()]);
                $actor->refresh();
            }
            $suffix = Str::lower(Str::random(8));
            $project = Project::query()->create(['project_code' => 'MAP-'.$suffix, 'name' => 'Mapping Verification', 'status' => 'Active']);
            $workspace = GeospatialWorkspace::query()->create(['project_id' => $project->id, 'workspace_code' => 'WS-'.$suffix, 'name' => 'Verifier', 'province' => 'DKI Jakarta', 'status' => 'Normal']);
            $station = MonitoringStation::query()->create(['workspace_id' => $workspace->id, 'station_code' => 'MST-'.$suffix, 'name' => 'Verifier Station']);
            $logger = DataLogger::query()->create(['monitoring_station_id' => $station->id, 'logger_code' => 'DL-'.$suffix, 'logger_model' => 'WX-100', 'vendor' => 'Verifier', 'logger_status' => 'Active']);

            $draft = $this->profiles->createDraft(['name' => 'Verifier WX', 'manufacturer' => 'Verifier', 'device_model' => 'WX-100'], $actor->id);
            try {
                $this->profiles->publish($draft, 'Invalid publish rehearsal', $actor->id);
                $this->check('invalid draft publish is rejected', false);
            } catch (MappingValidationException) {
                $this->check('invalid draft publish is rejected', true);
            }

            $unit = CanonicalUnit::query()->where('code', 'celsius')->firstOrFail();
            $parameter = CanonicalParameter::query()->where('key', 'air_temperature')->with('definition')->firstOrFail();
            $rule = $this->profiles->saveRule($draft, [
                'sort_order' => 1,
                'source_parameter' => 'Air_temp_raw',
                'source_item_key' => 'register_0',
                'parser' => 'uint16',
                'byte_offset' => 0,
                'byte_length' => 2,
                'register_start' => 0,
                'register_count' => 1,
                'signedness' => 'unsigned',
                'byte_order' => 'big',
                'word_order' => 'high_low',
                'scale' => '0.1',
                'offset' => '0',
                'source_unit_id' => $unit->id,
                'canonical_parameter_id' => $parameter->id,
                'canonical_parameter_version_id' => $parameter->definition->id,
                'missing_markers' => ['hex:ffff'],
                'origin' => 'RDM',
            ]);

            $beforePreview = $this->sideEffectCounts();
            $result = $this->preview->preview($rule, ['sample_format' => 'hex', 'sample' => '012e']);
            $this->check('production preview returns 30.20 Celsius', $result->status === 'value' && $result->value === '30.20' && $result->unitCode === 'celsius');
            $this->check('preview has no persistence side effects', $beforePreview === $this->sideEffectCounts());

            $publishedV1 = $this->profiles->publish($draft, 'Approved verifier mapping', $actor->id);
            $this->check('valid draft publishes with immutable snapshot', $publishedV1->status === 'published' && $publishedV1->validation_snapshot['valid'] === true);
            try {
                $publishedV1->rules()->firstOrFail()->update(['scale' => '99']);
                $this->check('published rule mutation is blocked', false);
            } catch (LogicException) {
                $this->check('published rule mutation is blocked', true);
            }

            $draftV2 = $this->profiles->clonePublished($publishedV1, 'Correct next version', $actor->id);
            $this->check('clone creates editable next version with copied rules', $draftV2->status === 'draft' && $draftV2->version === 2 && $draftV2->rules->count() === 1);
            $this->check('clone does not copy destination assignment', MappingAssignment::query()->count() === 0);
            $publishedV2 = $this->profiles->publish($draftV2, 'Publish corrected mapping', $actor->id);

            $assignment = $this->activations->activate('data_logger', $logger->id, $publishedV1, 'Initial activation', $actor->id);
            $this->check('first activation creates one exact-source winner', MappingAssignment::query()->count() === 1 && $assignment->active_version_id === $publishedV1->id);
            $assignment = $this->activations->activate('data_logger', $logger->id, $publishedV2, 'Switch to corrected mapping', $actor->id);
            $this->check('replacement keeps one winner and appends audit', MappingAssignment::query()->count() === 1 && $assignment->active_version_id === $publishedV2->id && MappingActivationLog::query()->count() === 2);
            $assignment = $this->activations->rollback($assignment, $publishedV1, 'Rollback rehearsal', $actor->id);
            $this->check('rollback restores prior published pointer with audit', $assignment->active_version_id === $publishedV1->id && MappingActivationLog::query()->count() === 3 && $assignment->lock_version === 3);

            $this->newLine();
            $this->info("Mapping workbench verification: {$this->passed} passed, {$this->failed} failed. Transaction will be rolled back.");
        } catch (Throwable $exception) {
            $this->error($exception::class.': '.$exception->getMessage());
            $this->failed++;
        } finally {
            DB::rollBack();
        }

        return $this->failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private int $passed = 0;

    private int $failed = 0;

    private function check(string $label, bool $passed): void
    {
        $this->line(($passed ? '<info>PASS</info> ' : '<error>FAIL</error> ').$label);
        $passed ? $this->passed++ : $this->failed++;
    }

    private function sideEffectCounts(): array
    {
        return [
            RawIngestionEvent::query()->count(),
            RawIngestionItem::query()->count(),
            TelemetryReading::query()->count(),
            MappingAssignment::query()->count(),
            MappingActivationLog::query()->count(),
        ];
    }
}
