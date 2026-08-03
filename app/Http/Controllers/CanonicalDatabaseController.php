<?php

namespace App\Http\Controllers;

use App\Models\CanonicalObservation;
use App\Models\CanonicalParameter;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CanonicalDatabaseController extends Controller
{
    public function index(): View
    {
        return view('modules.canonical-database.index', [
            'canonicalDomains' => $this->canonicalDomains(),
            'canonicalParameters' => Schema::hasTable('canonical_parameters')
                ? CanonicalParameter::orderBy('domain')->orderBy('field_identity')->get()
                : collect(),
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
            'source_parameter' => ['required', 'string', 'max:255'],
            'source_unit' => ['nullable', 'string', 'max:255'],
            'register_address' => ['nullable', 'string', 'max:255'],
            'function_code' => ['nullable', 'string', 'max:255'],
            'value_type' => ['nullable', 'string', 'max:255'],
            'data_length' => ['nullable', 'integer', 'min:0'],
            'byte_order' => ['nullable', 'string', 'max:255'],
            'scale_factor' => ['nullable', 'numeric'],
            'offset' => ['nullable', 'numeric'],
            'value_interpretation' => ['nullable', 'string'],
            'canonical_parameter_id' => ['required', 'exists:canonical_parameters,id'],
            'value_origin' => ['required', Rule::in(['direct_measurement', 'device_processed'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['scale_factor'] = $data['scale_factor'] ?? 1;
        $data['offset'] = $data['offset'] ?? 0;

        SensorMappingProfile::updateOrCreate(
            ['profile_code' => $data['profile_code']],
            $data
        );

        return back()->with('message', 'Canonical mapping profile berhasil disimpan.');
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
        ]);

        unset($data['canonical_parameter_id']);
        $data['is_platform_processed'] = $request->boolean('is_platform_processed');

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
