<?php

namespace App\Http\Controllers;

use App\Models\CanonicalParameterVersion;
use App\Models\CanonicalUnit;
use App\Models\DataLogger;
use App\Models\MappingAssignment;
use App\Models\MappingProfile;
use App\Models\MappingProfileVersion;
use App\Models\MappingRule;
use App\Models\RawIngestionItem;
use App\Models\Sensor;
use App\Services\Mapping\MappingActivationService;
use App\Services\Mapping\MappingPreviewService;
use App\Services\Mapping\MappingProfileService;
use App\Services\Mapping\MappingValidationException;
use App\Services\Mapping\MappingValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class MappingWorkbenchController extends Controller
{
    public function __construct(
        private readonly MappingProfileService $profiles,
        private readonly MappingValidationService $validator,
        private readonly MappingPreviewService $preview,
        private readonly MappingActivationService $activations,
    ) {}

    public function index(): View
    {
        $available = Schema::hasTable('mapping_profiles');
        $profiles = $available
            ? MappingProfile::query()->with(['versions.rules'])->withCount('versions')->orderBy('manufacturer')->orderBy('device_model')->get()
            : collect();

        return view('modules.mapping-workbench.index', compact('profiles', 'available'));
    }

    public function storeProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'manufacturer' => ['required', 'string', 'max:120'],
            'device_model' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $version = $this->profiles->createDraft($data, $request->user()?->id);

        return redirect()->route('mapping-workbench.show', $version)->with('status', 'Draft mapping profile berhasil dibuat.');
    }

    public function show(Request $request, MappingProfileVersion $version): View
    {
        $version->load(['profile.versions.rules', 'rules.sourceUnit', 'rules.canonicalParameter', 'rules.canonicalDefinition.unit']);
        $definitions = CanonicalParameterVersion::query()->with(['parameter', 'unit'])
            ->whereHas('parameter', fn ($query) => $query->where('lifecycle', 'active'))
            ->orderBy('canonical_parameter_id')->orderByDesc('version')->get()->unique('canonical_parameter_id')->values();
        $units = CanonicalUnit::query()->where('is_active', true)->orderBy('dimension_key')->orderBy('code')->get();
        $assignments = MappingAssignment::query()->with(['activeVersion.profile', 'activationLogs'])->orderBy('scope_key')->get();
        $rawItems = Schema::hasTable('raw_ingestion_items')
            ? RawIngestionItem::query()->with('event')->latest('id')->limit(50)->get()
            : collect();
        $dataLoggers = DataLogger::query()->orderBy('logger_code')->get();
        $sensors = Sensor::query()->orderBy('sensor_code')->get();
        $validation = $this->validator->validate($version);
        $editRule = $version->status === 'draft' && $request->integer('edit_rule')
            ? $version->rules->firstWhere('id', $request->integer('edit_rule'))
            : null;

        return view('modules.mapping-workbench.show', compact('version', 'definitions', 'units', 'assignments', 'rawItems', 'dataLoggers', 'sensors', 'validation', 'editRule'));
    }

    public function saveRule(Request $request, MappingProfileVersion $version): RedirectResponse
    {
        $data = $request->validate([
            'rule_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'source_parameter' => ['required', 'string', 'max:160'],
            'source_item_key' => ['nullable', 'string', 'max:160'],
            'parser' => ['required', Rule::in(['boolean', 'uint16', 'int16', 'uint32', 'int32', 'float32', 'decimal', 'text'])],
            'byte_offset' => ['required', 'integer', 'min:0', 'max:1048576'],
            'byte_length' => ['required', 'integer', 'min:1', 'max:1024'],
            'register_start' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'register_count' => ['nullable', 'integer', 'min:1', 'max:125'],
            'signedness' => ['required', Rule::in(['signed', 'unsigned', 'not_applicable'])],
            'byte_order' => ['required', Rule::in(['big', 'little'])],
            'word_order' => ['required', Rule::in(['high_low', 'low_high'])],
            'scale' => ['required', 'string', 'max:80'],
            'offset' => ['required', 'string', 'max:80'],
            'source_unit_id' => ['required', 'integer', 'exists:canonical_units,id'],
            'canonical_parameter_version_id' => ['required', 'integer', 'exists:canonical_parameter_versions,id'],
            'missing_markers_text' => ['nullable', 'string', 'max:2000'],
            'origin' => ['required', Rule::in(['RDM', 'RDP'])],
        ]);
        $definition = CanonicalParameterVersion::query()->findOrFail($data['canonical_parameter_version_id']);
        $data['canonical_parameter_id'] = $definition->canonical_parameter_id;
        $data['missing_markers'] = collect(preg_split('/\R/', (string) ($data['missing_markers_text'] ?? '')))
            ->map(fn ($value) => trim($value))->filter()->values()->all();
        unset($data['missing_markers_text']);
        $rule = ! empty($data['rule_id']) ? MappingRule::query()->findOrFail($data['rule_id']) : null;
        unset($data['rule_id']);

        try {
            $this->profiles->saveRule($version, $data, $rule);
        } catch (Throwable $exception) {
            return back()->withInput()->withErrors(['rule' => $exception->getMessage()]);
        }

        return redirect()->route('mapping-workbench.show', $version)->with('status', 'Mapping rule tersimpan.');
    }

    public function destroyRule(MappingProfileVersion $version, MappingRule $rule): RedirectResponse
    {
        if ($rule->mapping_profile_version_id !== $version->id || $version->status !== 'draft') {
            abort(409, 'Published or unrelated rules cannot be deleted.');
        }
        $rule->delete();

        return back()->with('status', 'Draft rule dihapus.');
    }

    public function validateVersion(MappingProfileVersion $version): RedirectResponse
    {
        $validation = $this->validator->validate($version);

        return back()->with($validation['valid'] ? 'status' : 'warning', $validation['valid']
            ? 'Draft valid dan siap dipublish.'
            : implode(' ', $validation['errors']));
    }

    public function preview(Request $request, MappingProfileVersion $version): RedirectResponse
    {
        $data = $request->validate([
            'rule_id' => ['required', 'integer', 'exists:mapping_rules,id'],
            'raw_item_id' => ['nullable', 'integer', 'exists:raw_ingestion_items,id'],
            'sample_format' => ['required_without:raw_item_id', Rule::in(['text', 'hex'])],
            'sample' => ['nullable', 'string', 'max:131072'],
        ]);
        $rule = $version->rules()->findOrFail($data['rule_id']);
        try {
            $result = $this->preview->preview($rule, $data);
        } catch (Throwable $exception) {
            return back()->withInput()->withErrors(['preview' => $exception->getMessage()]);
        }

        return back()->with('preview_result', $result->toArray());
    }

    public function publish(Request $request, MappingProfileVersion $version): RedirectResponse
    {
        $data = $request->validate(['change_reason' => ['required', 'string', 'max:500']]);
        try {
            $published = $this->profiles->publish($version, $data['change_reason'], $request->user()?->id);
        } catch (MappingValidationException $exception) {
            return back()->withErrors(['publish' => implode(' ', $exception->errors)]);
        } catch (Throwable $exception) {
            return back()->withErrors(['publish' => $exception->getMessage()]);
        }

        return redirect()->route('mapping-workbench.show', $published)->with('status', 'Version dipublish dan sekarang immutable.');
    }

    public function clone(Request $request, MappingProfileVersion $version): RedirectResponse
    {
        $data = $request->validate(['change_reason' => ['nullable', 'string', 'max:500']]);
        try {
            $draft = $this->profiles->clonePublished($version, (string) ($data['change_reason'] ?? ''), $request->user()?->id);
        } catch (Throwable $exception) {
            return back()->withErrors(['clone' => $exception->getMessage()]);
        }

        return redirect()->route('mapping-workbench.show', $draft)->with('status', 'Draft baru dibuat. Destination assignment tidak disalin.');
    }

    public function activate(Request $request, MappingProfileVersion $version): RedirectResponse
    {
        $data = $request->validate([
            'source' => ['required', 'regex:/^(data_logger|sensor):[1-9][0-9]*$/'],
            'activation_reason' => ['required', 'string', 'max:500'],
        ]);
        [$type, $id] = explode(':', $data['source'], 2);
        try {
            $this->activations->activate($type, (int) $id, $version, $data['activation_reason'], $request->user()?->id);
        } catch (MappingValidationException $exception) {
            return back()->withErrors(['activation' => implode(' ', $exception->errors)]);
        } catch (Throwable $exception) {
            return back()->withErrors(['activation' => $exception->getMessage()]);
        }

        return back()->with('status', 'Mapping aktif untuk source '.$data['source'].'.');
    }

    public function rollback(Request $request, MappingAssignment $assignment): RedirectResponse
    {
        $data = $request->validate([
            'target_version_id' => ['required', 'integer', 'exists:mapping_profile_versions,id'],
            'rollback_reason' => ['required', 'string', 'max:500'],
        ]);
        try {
            $target = MappingProfileVersion::query()->findOrFail($data['target_version_id']);
            $this->activations->rollback($assignment, $target, $data['rollback_reason'], $request->user()?->id);
        } catch (Throwable $exception) {
            return back()->withErrors(['rollback' => $exception->getMessage()]);
        }

        return back()->with('status', 'Assignment berhasil di-rollback.');
    }
}
