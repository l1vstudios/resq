<?php

namespace App\Http\Controllers;

use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\Project;
use App\Models\Province;
use App\Models\ResponsePlan;
use App\Models\Sensor;
use App\Models\WarningStation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectSetupController extends Controller
{
    public function index(): View
    {
        return view('modules.projects.index', $this->viewData());
    }

    public function storeProject(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'owner' => ['nullable', 'string', 'max:255'],
            'project_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        Project::updateOrCreate(['project_code' => $data['project_code']], $data);

        return back()->with('message', 'Project berhasil disimpan.');
    }

    public function storeWorkspace(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:resq_projects,id'],
            'workspace_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'hazard' => ['nullable', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'beneficiaries' => ['nullable', 'integer', 'min:0'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'status' => ['required', 'string', 'max:50'],
        ]);
        $data = $this->applyProvinceCoordinates($data);

        GeospatialWorkspace::updateOrCreate(['workspace_code' => $data['workspace_code']], $data);

        return back()->with('message', 'Geospatial workspace berhasil disimpan.');
    }

    public function storeMonitoringStation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'station_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'coordinate' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'logger_id' => ['nullable', 'string', 'max:255'],
            'logger_status' => ['required', 'string', 'max:50'],
            'connectivity_status' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
        ]);
        $data = $this->applyParsedCoordinate($data);

        MonitoringStation::updateOrCreate(['station_code' => $data['station_code']], $data);

        return back()->with('message', 'Monitoring station berhasil disimpan.');
    }

    public function storeWarningStation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'monitoring_station_id' => ['nullable', 'exists:monitoring_stations,id'],
            'station_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'zone_id' => ['nullable', 'string', 'max:255'],
            'coordinate' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'controller_id' => ['nullable', 'string', 'max:255'],
            'controller_model' => ['nullable', 'string', 'max:255'],
            'controller_vendor' => ['nullable', 'string', 'max:255'],
            'controller_status' => ['required', 'string', 'max:50'],
            'output_devices' => ['nullable', 'array'],
            'status' => ['required', 'string', 'max:50'],
            'public_warning_enabled' => ['nullable', 'boolean'],
            'ack_response' => ['nullable', 'string', 'max:255'],
        ]);
        $data['public_warning_enabled'] = $request->boolean('public_warning_enabled');
        $data = $this->applyParsedCoordinate($data);

        WarningStation::updateOrCreate(['station_code' => $data['station_code']], $data);

        return back()->with('message', 'Warning station berhasil disimpan.');
    }

    public function storeSensor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:geospatial_workspaces,id'],
            'monitoring_station_id' => ['required', 'exists:monitoring_stations,id'],
            'warning_station_id' => ['nullable', 'exists:warning_stations,id'],
            'mst_prefix_id' => ['required', 'exists:mst_prefixes,id'],
            'slave_id' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'function_code' => ['required', Rule::in(['FC01', 'FC02', 'FC03', 'FC04'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:125'],
            'poll_interval_ms' => ['required', 'integer', 'min:250', 'max:60000'],
            'sensor_code' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([
                'water_level',
                'rain_gauge',
                'tide_level',
                'seismic_vibration',
                'ground_movement',
                'soil_moisture',
                'river_flow',
                'weather_station',
                'temperature',
                'humidity',
                'pressure',
                'wind_speed',
                'wind_direction',
                'battery_bms',
                'solar_charger',
                'device_health',
            ])],
            'parameter' => ['nullable', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:255'],
            'threshold' => ['nullable', 'string', 'max:255'],
            'data_type' => ['required', Rule::in([
                'float32',
                'float64',
                'int8',
                'int16',
                'int32',
                'int64',
                'uint8',
                'uint16',
                'uint32',
                'uint64',
                'boolean',
                'string',
                'ascii',
                'hex',
                'byte',
                'raw',
            ])],
            'scale_factor' => ['required', 'numeric'],
            'offset' => ['required', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'reading_method' => ['required', 'string', 'max:50'],
            'alert_level' => ['required', 'string', 'max:50'],
            'rule' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $addressAlreadyUsed = Sensor::query()
            ->where('mst_prefix_id', $data['mst_prefix_id'])
            ->where('slave_id', $data['slave_id'])
            ->where('address', $data['address'])
            ->where('sensor_code', '!=', $data['sensor_code'])
            ->exists();

        if ($addressAlreadyUsed) {
            return back()
                ->withErrors(['address' => 'Kombinasi Prefix Sensors, Slave ID, dan Address sudah dipakai sensor lain.'])
                ->withInput();
        }

        Sensor::updateOrCreate(['sensor_code' => $data['sensor_code']], $data);

        return back()->with('message', 'Sensor berhasil disimpan.');
    }

    public function storeResponsePlan(Request $request): RedirectResponse
    {
        ResponsePlan::create([
            ...$request->validate([
                'workspace_id' => ['nullable', 'exists:geospatial_workspaces,id'],
                'sensor_id' => ['nullable', 'exists:sensors,id'],
                'warning_station_id' => ['nullable', 'exists:warning_stations,id'],
                'notes' => ['nullable', 'string'],
            ]),
            'dashboard_notif' => $request->boolean('dashboard_notif'),
            'sms_blasting' => $request->boolean('sms_blasting'),
            'warning_station_act' => $request->boolean('warning_station_act'),
        ]);

        return back()->with('message', 'Response plan berhasil ditambahkan.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $models = [
            'project' => Project::class,
            'workspace' => GeospatialWorkspace::class,
            'monitoring' => MonitoringStation::class,
            'warning' => WarningStation::class,
            'sensor' => Sensor::class,
            'response-plan' => ResponsePlan::class,
        ];

        abort_unless(isset($models[$type]), 404);
        $models[$type]::findOrFail($id)->delete();

        return back()->with('message', 'Data berhasil dihapus.');
    }

    private function viewData(): array
    {
        $provinces = $this->provinces();

        if (! Schema::hasTable('resq_projects')) {
            return [
                'project' => config('resq_dummy.project'),
                'projects' => collect([]),
                'clusters' => collect(config('resq_dummy.clusters')),
                'monitoringStations' => collect(config('resq_dummy.monitoring_stations')),
                'warningStations' => collect(config('resq_dummy.warning_stations')),
                'sensors' => collect(config('resq_dummy.sensors')),
                'mstPrefixes' => collect([]),
                'responsePlans' => collect([]),
                'provinces' => $provinces,
                'databaseReady' => false,
            ];
        }

        $projectModels = Project::latest()->get();
        $workspaceModels = GeospatialWorkspace::with('project')->latest()->get();
        $monitoringModels = MonitoringStation::with('workspace')->latest()->get();
        $warningModels = WarningStation::with(['workspace', 'monitoringStation'])->latest()->get();
        $sensorModels = Sensor::with(['workspace', 'monitoringStation', 'warningStation', 'mstPrefix'])->latest()->get();
        $mstPrefixes = Schema::hasTable('mst_prefixes') ? MstPrefix::latest()->get() : collect();

        $projects = $projectModels->map(fn (Project $project) => [
            'db_id' => $project->id,
            'id' => $project->project_code,
            'name' => $project->name,
            'owner' => $project->owner,
            'date' => $project->project_date,
            'status' => $project->status,
        ]);

        $workspaces = $workspaceModels->map(fn (GeospatialWorkspace $workspace) => [
            'db_id' => $workspace->id,
            'project_db_id' => $workspace->project_id,
            'id' => $workspace->workspace_code,
            'project_id' => $workspace->project?->project_code,
            'name' => $workspace->name,
            'hazard' => $workspace->hazard,
            'province' => $workspace->province,
            'city' => $workspace->city,
            'beneficiaries' => $workspace->beneficiaries,
            'latitude' => $workspace->latitude,
            'longitude' => $workspace->longitude,
            'status' => $workspace->status,
            'monitoring_station_id' => optional($monitoringModels->firstWhere('workspace_id', $workspace->id))->station_code,
            'warning_station_id' => optional($warningModels->firstWhere('workspace_id', $workspace->id))->station_code,
        ]);

        $monitoringStations = $monitoringModels->map(fn (MonitoringStation $station) => [
            'db_id' => $station->id,
            'workspace_db_id' => $station->workspace_id,
            'id' => $station->station_code,
            'cluster_id' => $station->workspace?->workspace_code,
            'name' => $station->name,
            'coordinate' => $station->coordinate,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'logger_id' => $station->logger_id,
            'logger_status' => $station->logger_status,
            'connectivity_status' => $station->connectivity_status,
            'warning_station_id' => optional($warningModels->firstWhere('monitoring_station_id', $station->id))->station_code,
            'status' => $station->status,
        ]);

        $warningStations = $warningModels->map(fn (WarningStation $station) => [
            'db_id' => $station->id,
            'workspace_db_id' => $station->workspace_id,
            'monitoring_station_db_id' => $station->monitoring_station_id,
            'id' => $station->station_code,
            'cluster_id' => $station->workspace?->workspace_code,
            'source_monitoring_station_id' => $station->monitoringStation?->station_code,
            'name' => $station->name,
            'zone_id' => $station->zone_id,
            'coordinate' => $station->coordinate,
            'latitude' => $station->latitude,
            'longitude' => $station->longitude,
            'controller_id' => $station->controller_id,
            'controller_model' => $station->controller_model,
            'controller_vendor' => $station->controller_vendor,
            'controller_status' => $station->controller_status,
            'output_devices' => $station->output_devices ?? [],
            'status' => $station->status,
            'public_warning_enabled' => $station->public_warning_enabled,
            'ack_response' => $station->ack_response,
        ]);

        $sensors = $sensorModels->map(fn (Sensor $sensor) => [
            'db_id' => $sensor->id,
            'workspace_db_id' => $sensor->workspace_id,
            'monitoring_station_db_id' => $sensor->monitoring_station_id,
            'warning_station_db_id' => $sensor->warning_station_id,
            'mst_prefix_db_id' => $sensor->mst_prefix_id,
            'id' => $sensor->sensor_code,
            'cluster_id' => $sensor->workspace?->workspace_code,
            'monitoring_station_id' => $sensor->monitoringStation?->station_code,
            'warning_station_id' => $sensor->warningStation?->station_code,
            'mst_prefix' => $sensor->mstPrefix?->prefix_code,
            'slave_id' => $sensor->slave_id,
            'address' => $sensor->address,
            'function_code' => $sensor->function_code ?? 'FC03',
            'quantity' => $sensor->quantity ?? 1,
            'poll_interval_ms' => $sensor->poll_interval_ms ?? 1000,
            'type' => $sensor->type,
            'parameter' => $sensor->parameter,
            'value' => $sensor->value,
            'threshold' => $sensor->threshold,
            'data_type' => $sensor->data_type,
            'scale_factor' => $sensor->scale_factor,
            'offset' => $sensor->offset,
            'unit' => $sensor->unit,
            'reading_method' => $sensor->reading_method,
            'alert_level' => $sensor->alert_level,
            'rule' => $sensor->rule,
            'status' => $sensor->status,
            'last_seen' => optional($sensor->last_seen_at)->diffForHumans(),
        ]);

        return [
            'project' => $projects->first() ?? config('resq_dummy.project'),
            'projects' => $projects,
            'clusters' => $workspaces,
            'monitoringStations' => $monitoringStations,
            'warningStations' => $warningStations,
            'sensors' => $sensors,
            'mstPrefixes' => $mstPrefixes,
            'responsePlans' => ResponsePlan::latest()->get(),
            'provinces' => $provinces,
            'databaseReady' => true,
        ];
    }

    private function provinces(): array
    {
        if (Schema::hasTable('provinces')) {
            return Province::query()
                ->orderBy('name')
                ->pluck('name')
                ->all();
        }

        return config('indonesia.provinces') ?? [];
    }

    private function applyProvinceCoordinates(array $data): array
    {
        if ((! empty($data['latitude']) && ! empty($data['longitude'])) || ! Schema::hasTable('provinces')) {
            return $data;
        }

        $province = Province::where('name', $data['province'])->first();

        if ($province) {
            $data['latitude'] = $province->latitude;
            $data['longitude'] = $province->longitude;
        }

        return $data;
    }

    private function applyParsedCoordinate(array $data): array
    {
        if ((! empty($data['latitude']) && ! empty($data['longitude'])) || empty($data['coordinate'])) {
            return $data;
        }

        $parts = array_map('trim', explode(',', $data['coordinate']));

        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            $data['latitude'] = $parts[0];
            $data['longitude'] = $parts[1];
        }

        return $data;
    }
}
