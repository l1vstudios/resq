<?php

namespace App\Http\Controllers;

use App\Models\CanonicalObservation;
use App\Models\CanonicalParameter;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CanonicalDatabaseController extends Controller
{
    public function index(): View
    {
        $canonicalParameters = Schema::hasTable('canonical_parameters')
            ? CanonicalParameter::orderBy('domain')->orderBy('field_identity')->get()
            : collect();

        return view('modules.canonical-database.index', [
            'canonicalDomains' => $this->canonicalDomains(),
            'canonicalParameters' => $canonicalParameters,
            'canonicalParameterChoices' => $canonicalParameters
                ->map(fn (CanonicalParameter $param) => [
                    'id' => $param->id,
                    'field_identity' => $param->field_identity,
                    'canonical_unit' => $param->canonical_unit,
                    'domain' => $param->domain,
                ])
                ->values(),
            'sensorMappingProfiles' => Schema::hasTable('sensor_mapping_profiles')
                ? SensorMappingProfile::with(['sensor', 'canonicalParameter'])->latest()->get()
                : collect(),
            'canonicalObservations' => Schema::hasTable('canonical_observations')
                ? CanonicalObservation::with(['monitoringStation', 'sensor'])
                    ->latest('observed_at')
                    ->latest()
                    ->limit(100)
                    ->get()
                : collect(),
            'sensors' => Schema::hasTable('sensors')
                ? Sensor::orderBy('sensor_code')->get()
                : collect(),
            'mappingPresets' => $this->mappingPresets(),
            'sensorPresets' => $this->mappingPresets(false),
        ]);
    }

    public function storeMapping(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sensor_id' => ['required', 'exists:sensors,id'],
            'profile_code' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'communication_path' => ['nullable', 'string', 'max:255'],
            'slave_id' => ['nullable', 'integer', 'min:0'],
            'source_parameter' => ['nullable', 'required_without:canonical_parameter_ids', 'string', 'max:255'],
            'source_unit' => ['nullable', 'string', 'max:255'],
            'register_address' => ['nullable', 'string', 'max:255'],
            'function_code' => ['nullable', 'string', 'max:255'],
            'value_type' => ['nullable', 'string', 'max:255'],
            'data_length' => ['nullable', 'integer', 'min:0'],
            'byte_order' => ['nullable', 'string', 'max:255'],
            'scale_factor' => ['nullable', 'numeric'],
            'offset' => ['nullable', 'numeric'],
            'value_interpretation' => ['nullable', 'string'],
            'canonical_parameter_id' => ['nullable', 'required_without:canonical_parameter_ids', 'exists:canonical_parameters,id'],
            'canonical_parameter_ids' => ['nullable', 'array'],
            'canonical_parameter_ids.*' => ['integer', 'exists:canonical_parameters,id'],
            'bulk_preset_key' => ['nullable', 'string', 'max:255'],
            'value_origin' => ['required', Rule::in(['direct_measurement', 'device_processed'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['scale_factor'] = $data['scale_factor'] ?? 1;
        $data['offset'] = $data['offset'] ?? 0;

        $bulkParameterIds = collect($data['canonical_parameter_ids'] ?? [])
            ->filter()
            ->unique()
            ->values();

        if ($bulkParameterIds->isNotEmpty()) {
            $baseAddress = is_numeric($data['register_address'] ?? null) ? (int) $data['register_address'] : 0;
            $parameters = CanonicalParameter::whereIn('id', $bulkParameterIds)->get()->keyBy('id');
            $devicePreset = $this->mappingPresetByKey($data['bulk_preset_key'] ?? null);

            if (! $devicePreset) {
                return back()
                    ->withErrors(['canonical_parameter_ids' => 'Sensor preset tidak ditemukan. Buat mapping profile referensi untuk model sensor ini terlebih dahulu.'])
                    ->withInput();
            }

            $presetParameters = collect($devicePreset['parameters'])->keyBy('canonical_parameter_id');
            $createdCount = 0;

            foreach ($bulkParameterIds as $parameterId) {
                $parameter = $parameters->get($parameterId);

                if (! $parameter) {
                    continue;
                }

                $preset = $presetParameters->get((int) $parameterId, []);
                $profileData = $data;
                unset($profileData['canonical_parameter_ids'], $profileData['bulk_preset_key']);

                $profileData['canonical_parameter_id'] = $parameter->id;
                $profileData['profile_code'] = $this->mappingProfileCode($data['profile_code'], $parameter->field_identity);
                $profileData['manufacturer'] = $data['manufacturer'] ?: ($devicePreset['manufacturer'] ?? 'Rika Sensor');
                $profileData['device_model'] = $data['device_model'] ?: ($devicePreset['device_model'] ?? null);
                $profileData['communication_path'] = $data['communication_path'] ?: ($devicePreset['communication_path'] ?? 'RS485 Modbus RTU');
                $profileData['source_parameter'] = $preset['source_parameter'] ?? $parameter->field_identity;
                $profileData['source_unit'] = $preset['source_unit'] ?? $parameter->canonical_unit;
                $profileData['register_address'] = (string) ($baseAddress + ($preset['register_offset'] ?? $createdCount));
                $profileData['function_code'] = $preset['function_code'] ?? $data['function_code'] ?? 'FC03';
                $profileData['value_type'] = $preset['value_type'] ?? $data['value_type'] ?? null;
                $profileData['data_length'] = $preset['data_length'] ?? $data['data_length'] ?? null;
                $profileData['byte_order'] = $preset['byte_order'] ?? $data['byte_order'] ?? null;

                SensorMappingProfile::updateOrCreate(
                    ['profile_code' => $profileData['profile_code']],
                    $profileData
                );
                $createdCount++;
            }

            return back()->with('message', $createdCount . ' mapping profile bulk berhasil disimpan.');
        }

        unset($data['canonical_parameter_ids'], $data['bulk_preset_key']);

        SensorMappingProfile::updateOrCreate(
            ['profile_code' => $data['profile_code']],
            $data
        );

        return back()->with('message', 'Canonical mapping profile berhasil disimpan.');
    }

    public function storePreset(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('sensor_mapping_presets') || ! Schema::hasTable('sensor_mapping_preset_items')) {
            return back()->withErrors(['label' => 'Tabel sensor mapping preset belum tersedia. Jalankan migration terlebih dahulu.'])->withInput();
        }

        $presetId = $request->integer('preset_id') ?: null;
        $data = $request->validate([
            'preset_id' => ['nullable', 'integer', 'exists:sensor_mapping_presets,id'],
            'label' => ['required', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'device_model' => ['required', 'string', 'max:255'],
            'communication_path' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.canonical_parameter_id' => ['required', 'integer', 'distinct', 'exists:canonical_parameters,id'],
            'items.*.source_parameter' => ['required', 'string', 'max:255'],
            'items.*.source_unit' => ['nullable', 'string', 'max:255'],
            'items.*.register_offset' => ['required', 'integer', 'min:0'],
            'items.*.function_code' => ['nullable', 'string', 'max:255'],
            'items.*.value_type' => ['nullable', 'string', 'max:255'],
            'items.*.data_length' => ['nullable', 'integer', 'min:1'],
            'items.*.byte_order' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $presetId) {
            $now = now();

            if ($presetId) {
                DB::table('sensor_mapping_presets')
                    ->where('id', $presetId)
                    ->update([
                        'label' => $data['label'],
                        'manufacturer' => $data['manufacturer'] ?? null,
                        'device_model' => $data['device_model'],
                        'communication_path' => $data['communication_path'] ?? null,
                        'description' => $data['description'] ?? null,
                        'status' => $data['status'],
                        'updated_at' => $now,
                    ]);

                $currentPresetId = $presetId;
            } else {
                $presetKey = $this->uniquePresetKey($data['manufacturer'] ?? null, $data['device_model'], $data['label']);

                $currentPresetId = DB::table('sensor_mapping_presets')->insertGetId([
                    'preset_key' => $presetKey,
                    'label' => $data['label'],
                    'manufacturer' => $data['manufacturer'] ?? null,
                    'device_model' => $data['device_model'],
                    'communication_path' => $data['communication_path'] ?? null,
                    'description' => $data['description'] ?? null,
                    'status' => $data['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('sensor_mapping_preset_items')
                ->where('sensor_mapping_preset_id', $currentPresetId)
                ->delete();

            $items = collect($data['items'])
                ->values()
                ->map(fn (array $item, int $index) => [
                    'sensor_mapping_preset_id' => $currentPresetId,
                    'canonical_parameter_id' => $item['canonical_parameter_id'],
                    'source_parameter' => $item['source_parameter'],
                    'source_unit' => $item['source_unit'] ?? null,
                    'register_offset' => $item['register_offset'],
                    'function_code' => $item['function_code'] ?? null,
                    'value_type' => $item['value_type'] ?? null,
                    'data_length' => $item['data_length'] ?? null,
                    'byte_order' => $item['byte_order'] ?? null,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            DB::table('sensor_mapping_preset_items')->insert($items);
        });

        return back()->with('message', 'Sensor preset berhasil disimpan dan siap dipakai untuk bulk mapping.');
    }

    public function destroyPreset(int $preset): RedirectResponse
    {
        if (Schema::hasTable('sensor_mapping_presets')) {
            DB::table('sensor_mapping_presets')->where('id', $preset)->delete();
        }

        return back()->with('message', 'Sensor preset berhasil dihapus.');
    }

    private function mappingProfileCode(string $baseCode, string $fieldIdentity): string
    {
        $suffix = Str::of($fieldIdentity)->snake()->replace('_', '-')->upper();

        return Str::limit(trim($baseCode . '-' . $suffix, '-'), 255, '');
    }

    private function mappingPresets(bool $activeOnly = true): array
    {
        if (Schema::hasTable('sensor_mapping_presets') && Schema::hasTable('sensor_mapping_preset_items')) {
            $query = DB::table('sensor_mapping_presets');

            if ($activeOnly) {
                $query->where('status', 'active');
            }

            $hasPresetItemProtocol = Schema::hasColumn('sensor_mapping_preset_items', 'value_type');

            return $query->orderBy('label')
                ->get()
                ->map(function ($preset) use ($hasPresetItemProtocol) {
                    $columns = [
                        'sensor_mapping_preset_items.canonical_parameter_id',
                        'canonical_parameters.field_identity',
                        'canonical_parameters.canonical_unit',
                        'sensor_mapping_preset_items.source_parameter',
                        'sensor_mapping_preset_items.source_unit',
                        'sensor_mapping_preset_items.register_offset',
                    ];

                    if ($hasPresetItemProtocol) {
                        $columns = array_merge($columns, [
                            'sensor_mapping_preset_items.function_code',
                            'sensor_mapping_preset_items.value_type',
                            'sensor_mapping_preset_items.data_length',
                            'sensor_mapping_preset_items.byte_order',
                        ]);
                    }

                    $parameters = DB::table('sensor_mapping_preset_items')
                        ->join('canonical_parameters', 'canonical_parameters.id', '=', 'sensor_mapping_preset_items.canonical_parameter_id')
                        ->where('sensor_mapping_preset_items.sensor_mapping_preset_id', $preset->id)
                        ->orderBy('sensor_mapping_preset_items.sort_order')
                        ->orderBy('sensor_mapping_preset_items.register_offset')
                        ->get($columns)
                        ->map(function ($item) use ($hasPresetItemProtocol) {
                            return [
                                'canonical_parameter_id' => (int) $item->canonical_parameter_id,
                                'field_identity' => $item->field_identity,
                                'canonical_unit' => $item->canonical_unit,
                                'source_parameter' => $item->source_parameter,
                                'source_unit' => $item->source_unit,
                                'register_offset' => (int) $item->register_offset,
                                'function_code' => $hasPresetItemProtocol ? $item->function_code : null,
                                'value_type' => $hasPresetItemProtocol ? $item->value_type : null,
                                'data_length' => $hasPresetItemProtocol ? $item->data_length : null,
                                'byte_order' => $hasPresetItemProtocol ? $item->byte_order : null,
                            ];
                        })
                        ->all();

                    return [
                        'id' => (int) $preset->id,
                        'key' => $preset->preset_key,
                        'label' => $preset->label,
                        'manufacturer' => $preset->manufacturer,
                        'device_model' => $preset->device_model,
                        'communication_path' => $preset->communication_path,
                        'description' => $preset->description,
                        'status' => $preset->status,
                        'parameters' => $parameters,
                    ];
                })
                ->values()
                ->all();
        }

        if (! Schema::hasTable('sensor_mapping_profiles')) {
            return [];
        }

        return SensorMappingProfile::with('canonicalParameter')
            ->whereNotNull('device_model')
            ->whereNotNull('canonical_parameter_id')
            ->get()
            ->filter(fn (SensorMappingProfile $profile) => $profile->canonicalParameter !== null)
            ->groupBy(fn (SensorMappingProfile $profile) => $this->presetKey($profile->manufacturer, $profile->device_model))
            ->map(function ($profiles, string $key) {
                $first = $profiles->first();
                $baseAddress = $profiles
                    ->map(fn (SensorMappingProfile $profile) => $this->integerAddress($profile->register_address))
                    ->filter(fn (?int $address) => $address !== null)
                    ->min();

                $parameters = $profiles
                    ->sortBy(fn (SensorMappingProfile $profile) => $this->integerAddress($profile->register_address) ?? PHP_INT_MAX)
                    ->map(function (SensorMappingProfile $profile) use ($baseAddress) {
                        $address = $this->integerAddress($profile->register_address);

                        return [
                            'canonical_parameter_id' => $profile->canonical_parameter_id,
                            'field_identity' => $profile->canonicalParameter?->field_identity,
                            'canonical_unit' => $profile->canonicalParameter?->canonical_unit,
                            'source_parameter' => $profile->source_parameter,
                            'source_unit' => $profile->source_unit,
                            'register_offset' => $address !== null && $baseAddress !== null ? max($address - $baseAddress, 0) : 0,
                            'function_code' => $profile->function_code,
                            'value_type' => $profile->value_type,
                            'data_length' => $profile->data_length,
                            'byte_order' => $profile->byte_order,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'id' => null,
                    'key' => $key,
                    'label' => trim(($first->manufacturer ? $first->manufacturer . ' ' : '') . $first->device_model),
                    'manufacturer' => $first->manufacturer,
                    'device_model' => $first->device_model,
                    'communication_path' => $first->communication_path,
                    'description' => null,
                    'status' => 'active',
                    'parameters' => $parameters,
                ];
            })
            ->values()
            ->all();
    }

    private function mappingPresetByKey(?string $key): ?array
    {
        return collect($this->mappingPresets())->firstWhere('key', $key);
    }

    private function presetKey(?string $manufacturer, ?string $deviceModel, ?string $label = null): string
    {
        $parts = array_filter([$manufacturer ?: 'unknown', $deviceModel ?: 'unknown', $label]);

        return Str::slug(implode('-', $parts));
    }

    private function uniquePresetKey(?string $manufacturer, ?string $deviceModel, ?string $label): string
    {
        $baseKey = Str::limit($this->presetKey($manufacturer, $deviceModel, $label), 245, '');
        $key = $baseKey;
        $counter = 2;

        while (DB::table('sensor_mapping_presets')->where('preset_key', $key)->exists()) {
            $key = $baseKey . '-' . $counter;
            $counter++;
        }

        return $key;
    }

    private function integerAddress(mixed $address): ?int
    {
        if ($address === null || $address === '') {
            return null;
        }

        return is_numeric($address) ? (int) $address : null;
    }

    public function storeParameter(Request $request): RedirectResponse
    {
        $parameterId = $request->integer('canonical_parameter_id') ?: null;
        $data = $request->validate([
            'canonical_parameter_id' => ['nullable', 'exists:canonical_parameters,id'],
            'field_identity' => [
                'required',
                'string',
                'max:255',
                Rule::unique('canonical_parameters', 'field_identity')->ignore($parameterId),
            ],
            'definition' => ['nullable', 'string'],
            'domain' => ['required', Rule::in(['meteorology', 'hydrology', 'geotechnical'])],
            'canonical_unit' => ['nullable', 'string', 'max:255'],
            'data_type' => ['required', 'string', 'max:255'],
            'measurement_characteristic' => ['nullable', 'string', 'max:255'],
            'formula' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_platform_processed' => ['nullable', 'boolean'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'resolution' => ['nullable', 'numeric', 'min:0'],
            'accuracy' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_note' => ['nullable', 'string'],
        ]);

        unset($data['canonical_parameter_id']);
        $data['is_platform_processed'] = $request->boolean('is_platform_processed');

        $inputRequirements = [
            'min_value' => $data['min_value'] ?? null,
            'max_value' => $data['max_value'] ?? null,
            'resolution' => $data['resolution'] ?? null,
            'accuracy' => $data['accuracy'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'source_reference' => $data['source_reference'] ?? null,
            'source_note' => $data['source_note'] ?? null,
        ];
        $data['input_requirements'] = $inputRequirements;

        unset(
            $data['min_value'],
            $data['max_value'],
            $data['resolution'],
            $data['accuracy'],
            $data['source_url'],
            $data['source_reference'],
            $data['source_note'],
        );

        if ($parameterId) {
            CanonicalParameter::findOrFail($parameterId)->update($data);
        } else {
            CanonicalParameter::create($data);
        }

        return back()->with('message', 'Canonical parameter berhasil disimpan.');
    }

    public function destroyParameter(CanonicalParameter $parameter): RedirectResponse
    {
        $parameter->delete();

        return back()->with('message', 'Canonical parameter berhasil dihapus.');
    }

    public function destroyMapping(SensorMappingProfile $profile): RedirectResponse
    {
        $profile->delete();

        return back()->with('message', 'Canonical mapping profile berhasil dihapus.');
    }

    private function canonicalDomains(): array
    {
        return [
            'meteorology' => [
                'title' => 'Meteorology',
                'description' => 'Weather and atmospheric readings such as temperature, humidity, rainfall, wind, and station health.',
                'groups' => ['Temperature', 'Humidity', 'Rainfall', 'Wind', 'Pressure', 'Device Health'],
            ],
            'hydrology' => [
                'title' => 'Hydrology',
                'description' => 'Water observation readings including river level, tide level, water velocity, and calculated discharge.',
                'groups' => ['Water Level', 'Tide Level', 'Velocity', 'Discharge'],
            ],
            'geotechnical' => [
                'title' => 'Geotechnical',
                'description' => 'Ground and slope monitoring readings such as soil moisture, tilt, vibration, and displacement.',
                'groups' => ['Soil Moisture', 'Tilt', 'Vibration', 'Displacement'],
            ],
        ];
    }
}
