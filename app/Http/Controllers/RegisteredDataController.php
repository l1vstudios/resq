<?php

namespace App\Http\Controllers;

use App\Models\ConnectivityConfig;
use App\Models\DataLogger;
use App\Models\DeviceCredential;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use App\Models\WarningStation;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RegisteredDataController extends Controller
{
    public function clusters(): View
    {
        return view('modules.clusters.index', $this->data());
    }

    public function monitoringStations(): View
    {
        return view('modules.monitoring-stations.index', $this->data());
    }

    public function warningStations(): View
    {
        return view('modules.warning-stations.index', $this->data());
    }

    public function sensors(): View
    {
        return view('modules.sensors.index', $this->data());
    }

    public function mstPrefixes(): View
    {
        return view('modules.mst-prefixes.index', $this->data());
    }

    public function dataLoggers(): View
    {
        return view('modules.data-loggers.index', $this->data());
    }

    public function connectivity(): View
    {
        return view('modules.connectivity.index', $this->data());
    }

    public function credentials(): View
    {
        return view('modules.credentials.index', $this->data());
    }

    public function telemetry(): View
    {
        return view('modules.telemetry.index', $this->data());
    }

    public function commandTest(): View
    {
        return view('modules.warning-stations.index', $this->data());
    }

    private function data(): array
    {
        if (! Schema::hasTable('resq_projects')) {
            return [
                'project' => config('resq_dummy.project'),
                'clusters' => collect(config('resq_dummy.clusters')),
                'monitoringStations' => collect(config('resq_dummy.monitoring_stations')),
                'warningStations' => collect(config('resq_dummy.warning_stations')),
                'sensors' => collect(config('resq_dummy.sensors')),
                'mstPrefixes' => collect([]),
                'dataLoggers' => collect(config('resq_dummy.data_loggers')),
                'connectivity' => collect(config('resq_dummy.connectivity')),
                'credentials' => collect(config('resq_dummy.credentials')),
                'wsControllers' => collect(config('resq_dummy.ws_controllers'))->keyBy('warning_station_id'),
            ];
        }

        $project = Project::latest()->first();
        $workspaces = GeospatialWorkspace::with(['project', 'monitoringStations', 'warningStations'])->latest()->get();
        $monitoringStations = MonitoringStation::with(['workspace', 'warningStations', 'sensors'])->latest()->get();
        $warningStations = WarningStation::with(['workspace', 'monitoringStation'])->latest()->get();
        $sensors = Sensor::with(['workspace', 'monitoringStation', 'warningStation', 'mstPrefix'])->latest()->get();
        $mstPrefixModels = Schema::hasTable('mst_prefixes') ? MstPrefix::latest()->get() : collect();
        $hasDataLoggers = Schema::hasTable('data_loggers');
        $hasConnectivity = Schema::hasTable('connectivity_configs');
        $hasCredentials = Schema::hasTable('device_credentials');

        $dataLoggerModels = $hasDataLoggers
            ? DataLogger::with('monitoringStation')->latest()->get()
            : collect();
        $connectivityModels = $hasConnectivity
            ? ConnectivityConfig::with('dataLogger')->latest()->get()
            : collect();
        $credentialModels = $hasCredentials
            ? DeviceCredential::with('dataLogger')->latest()->get()
            : collect();
        $telemetryModels = Schema::hasTable('telemetry_readings')
            ? TelemetryReading::with(['sensor.monitoringStation', 'dataLogger'])->latest('received_at')->latest()->limit(100)->get()
            : collect();

        $clusters = $workspaces->map(fn (GeospatialWorkspace $workspace) => [
            'db_id' => $workspace->id,
            'id' => $workspace->workspace_code,
            'project_id' => $workspace->project?->project_code,
            'name' => $workspace->name,
            'hazard' => $workspace->hazard,
            'province' => $workspace->province,
            'city' => $workspace->city,
            'beneficiaries' => $workspace->beneficiaries,
            'status' => $workspace->status,
            'monitoring_station_id' => $workspace->monitoringStations->first()?->station_code,
            'warning_station_id' => $workspace->warningStations->first()?->station_code,
        ]);

        $monitoring = $monitoringStations->map(fn (MonitoringStation $station) => [
            'db_id' => $station->id,
            'id' => $station->station_code,
            'cluster_id' => $station->workspace?->workspace_code,
            'name' => $station->name,
            'coordinate' => $station->coordinate,
            'logger_id' => $station->logger_id,
            'logger_status' => $station->logger_status,
            'connectivity_status' => $station->connectivity_status,
            'warning_station_id' => $station->warningStations->first()?->station_code,
            'status' => $station->status,
        ]);

        $warnings = $warningStations->map(fn (WarningStation $station) => [
            'db_id' => $station->id,
            'id' => $station->station_code,
            'cluster_id' => $station->workspace?->workspace_code,
            'source_monitoring_station_id' => $station->monitoringStation?->station_code,
            'name' => $station->name,
            'zone_id' => $station->zone_id,
            'coordinate' => $station->coordinate,
            'controller_id' => $station->controller_id,
            'controller_model' => $station->controller_model,
            'controller_vendor' => $station->controller_vendor,
            'controller_status' => $station->controller_status,
            'output_devices' => $station->output_devices ?? [],
            'status' => $station->status,
            'public_warning_enabled' => $station->public_warning_enabled,
            'ack_response' => $station->ack_response,
        ]);

        $sensorRows = $sensors->map(fn (Sensor $sensor) => [
            'db_id' => $sensor->id,
            'id' => $sensor->sensor_code,
            'cluster_id' => $sensor->workspace?->workspace_code,
            'monitoring_station_id' => $sensor->monitoringStation?->station_code,
            'warning_station_id' => $sensor->warningStation?->station_code,
            'mst_prefix' => $sensor->mstPrefix?->prefix_code,
            'slave_id' => $sensor->slave_id,
            'address' => $sensor->address,
            'type' => $sensor->type,
            'parameter' => $sensor->parameter,
            'value' => $sensor->value,
            'threshold' => $sensor->threshold,
            'data_type' => $sensor->data_type,
            'scale_factor' => $sensor->scale_factor,
            'offset' => $sensor->offset,
            'unit' => $sensor->unit,
            'status' => $sensor->status,
            'last_seen' => optional($sensor->last_seen_at)->diffForHumans(),
        ]);

        return [
            'project' => $project ? [
                'id' => $project->project_code,
                'name' => $project->name,
                'owner' => $project->owner,
                'date' => $project->project_date,
            ] : config('resq_dummy.project'),
            'clusters' => $clusters,
            'monitoringStations' => $monitoring,
            'warningStations' => $warnings,
            'sensors' => $sensorRows,
            'mstPrefixes' => $this->mstPrefixesFromModels($mstPrefixModels),
            'dataLoggers' => $hasDataLoggers
                ? $this->dataLoggersFromModels($dataLoggerModels)
                : $this->dataLoggersFromMonitoring($monitoring),
            'connectivity' => $hasConnectivity
                ? $this->connectivityFromModels($connectivityModels)
                : $this->connectivityFromMonitoring($monitoring),
            'credentials' => $hasCredentials
                ? $this->credentialsFromModels($credentialModels)
                : $this->credentialsFromMonitoring($monitoring),
            'telemetryReadings' => $this->telemetryFromModels($telemetryModels),
            'wsControllers' => $this->controllersFromWarnings($warnings),
        ];
    }

    private function mstPrefixesFromModels($prefixes)
    {
        return collect($prefixes)->map(fn (MstPrefix $prefix) => [
            'db_id' => $prefix->id,
            'id' => $prefix->prefix_code,
            'name' => $prefix->name,
            'description' => $prefix->description,
            'status' => $prefix->status,
            'sensors' => $prefix->sensors()->count(),
        ]);
    }

    private function dataLoggersFromModels($dataLoggers)
    {
        return collect($dataLoggers)->map(fn (DataLogger $logger) => [
            'db_id' => $logger->id,
            'id' => $logger->logger_code,
            'monitoring_station_db_id' => $logger->monitoring_station_id,
            'monitoring_station_id' => $logger->monitoringStation?->station_code,
            'serial_number' => $logger->serial_number,
            'logger_model' => $logger->logger_model,
            'vendor' => $logger->vendor,
            'firmware_version' => $logger->firmware_version,
            'device_label' => $logger->device_label,
            'logger_status' => $logger->logger_status,
        ]);
    }

    private function dataLoggersFromMonitoring($monitoringStations)
    {
        return collect($monitoringStations)
            ->filter(fn ($station) => ! empty($station['logger_id']))
            ->map(fn ($station) => [
                'id' => $station['logger_id'],
                'monitoring_station_id' => $station['id'],
                'serial_number' => $station['logger_id'],
                'logger_model' => 'Data Logger',
                'vendor' => '-',
                'firmware_version' => '-',
                'device_label' => $station['logger_id'],
                'logger_status' => $station['logger_status'],
            ])
            ->values();
    }

    private function connectivityFromMonitoring($monitoringStations)
    {
        return collect($monitoringStations)
            ->filter(fn ($station) => ! empty($station['logger_id']))
            ->map(fn ($station) => [
                'id' => 'CONN-' . $station['id'],
                'logger_id' => $station['logger_id'],
                'communication_type' => '-',
                'protocol' => '-',
                'host_or_endpoint' => '-',
                'port' => '-',
                'topic_or_api_path' => 'telemetry/' . $station['id'],
                'gateway_id' => '-',
                'sim_number' => '-',
                'imei' => '-',
                'apn' => '-',
                'connectivity_status' => $station['connectivity_status'],
            ])
            ->values();
    }

    private function connectivityFromModels($connectivity)
    {
        return collect($connectivity)->map(fn (ConnectivityConfig $item) => [
            'db_id' => $item->id,
            'data_logger_db_id' => $item->data_logger_id,
            'id' => $item->connectivity_code,
            'logger_id' => $item->dataLogger?->logger_code,
            'communication_type' => $item->communication_type,
            'protocol' => $item->protocol,
            'host_or_endpoint' => $item->host_or_endpoint,
            'port' => $item->port,
            'topic_or_api_path' => $item->topic_or_api_path,
            'gateway_id' => $item->gateway_id,
            'sim_number' => $item->sim_number,
            'imei' => $item->imei,
            'apn' => $item->apn,
            'connectivity_status' => $item->connectivity_status,
        ]);
    }

    private function credentialsFromMonitoring($monitoringStations)
    {
        return collect($monitoringStations)
            ->filter(fn ($station) => ! empty($station['logger_id']))
            ->map(fn ($station) => [
                'id' => 'CRED-' . $station['id'],
                'logger_id' => $station['logger_id'],
                'device_token' => 'linked-to-' . $station['logger_id'],
                'mqtt_username' => strtolower(str_replace('-', '_', $station['id'])),
                'mqtt_password_hash' => '-',
                'certificate_ref' => '-',
                'credential_status' => $station['logger_status'],
                'created_at' => '-',
                'revoked_at' => null,
            ])
            ->values();
    }

    private function credentialsFromModels($credentials)
    {
        return collect($credentials)->map(fn (DeviceCredential $credential) => [
            'db_id' => $credential->id,
            'data_logger_db_id' => $credential->data_logger_id,
            'id' => $credential->credential_code,
            'logger_id' => $credential->dataLogger?->logger_code,
            'device_token' => $credential->device_token,
            'mqtt_username' => $credential->mqtt_username,
            'mqtt_password_hash' => $credential->mqtt_password_hash,
            'certificate_ref' => $credential->certificate_ref,
            'credential_status' => $credential->credential_status,
            'created_at' => optional($credential->created_at)->format('Y-m-d H:i:s'),
            'revoked_at' => optional($credential->revoked_at)->format('Y-m-d H:i:s'),
        ]);
    }

    private function telemetryFromModels($readings)
    {
        return collect($readings)->map(fn (TelemetryReading $reading) => [
            'db_id' => $reading->id,
            'sensor_id' => $reading->sensor?->sensor_code,
            'monitoring_station_id' => $reading->sensor?->monitoringStation?->station_code,
            'data_logger_id' => $reading->dataLogger?->logger_code,
            'value' => $reading->value,
            'alert_level' => $reading->alert_level,
            'status' => $reading->status,
            'received_at' => optional($reading->received_at ?? $reading->created_at)->diffForHumans(),
        ]);
    }

    private function controllersFromWarnings($warningStations)
    {
        return collect($warningStations)
            ->mapWithKeys(fn ($station) => [
                $station['id'] => [
                    'id' => $station['controller_id'] ?: 'WSC-' . $station['id'],
                    'warning_station_id' => $station['id'],
                    'controller_model' => $station['controller_model'] ?: '-',
                    'vendor' => $station['controller_vendor'] ?: '-',
                    'serial_number' => $station['controller_id'] ?: '-',
                    'firmware_version' => '-',
                    'device_label' => $station['controller_id'] ?: $station['id'],
                    'controller_status' => $station['controller_status'],
                ],
            ]);
    }
}
