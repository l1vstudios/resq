<?php

namespace Database\Seeders;

use App\Models\CanonicalParameter;
use App\Models\ConnectivityConfig;
use App\Models\DataLogger;
use App\Models\HydrometHazardClassification;
use App\Models\HydrometEwsRelationship;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use App\Models\TelemetryReading;
use App\Services\SentinelRuntimeReadService;
use Illuminate\Database\Seeder;

class RikaCuacaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::where('project_code', 'EMP-DEMO-001')->firstOrFail();
        $workspace = $project->workspaces()->firstOrFail();
        $station = MonitoringStation::where('project_id', $project->id)
            ->where('station_code', 'MS-DEMO-01')
            ->firstOrFail();

        $logger = DataLogger::updateOrCreate(
            ['logger_code' => 'REDNODE-BLIIOT-011'],
            [
                'monitoring_station_id' => $station->id,
                'logger_status' => 'Active',
                'logger_model' => 'RedNode Bliiot',
                'vendor' => 'Bliiot',
                'device_label' => 'BL118-bliiot',
                'remote_host' => '192.168.3.1',
                'remote_ssh_port' => 22,
                'remote_ssh_user' => 'root',
                'remote_gateway_path' => '/root/rednode-gateway',
            ]
        );

        $prefix = MstPrefix::firstOrCreate(
            ['prefix_code' => 'RIKA'],
            ['name' => 'Rika Modbus Sensor', 'status' => 'Active']
        );
        $moisturePrefix = MstPrefix::firstOrCreate(
            ['prefix_code' => 'LEMBAB'],
            ['name' => 'Soil Moisture Sensor', 'status' => 'Active']
        );

        $sensor = Sensor::updateOrCreate(
            ['sensor_code' => 'RIKA-CUACA'],
            [
                'workspace_id' => $workspace->id,
                'monitoring_station_id' => $station->id,
                'data_logger_id' => $logger->id,
                'warning_station_id' => null,
                'mst_prefix_id' => $prefix->id,
                'slave_id' => '1',
                'address' => '0',
                'function_code' => 'FC03',
                'quantity' => 49,
                'poll_interval_ms' => 1000,
                'type' => 'weather_station',
                'parameter' => 'Weather Station',
                'data_type' => 'float32',
                'scale_factor' => 1,
                'offset' => 0,
                'unit' => '',
                'reading_method' => 'Absolute',
                'threshold' => null,
                'rule' => null,
                'alert_level' => 'Normal',
                'status' => 'Normal',
                'last_seen_at' => now(),
            ]
        );

        $moistureParameter = CanonicalParameter::firstOrCreate(
            ['field_identity' => 'SoilMoisture'],
            [
                'definition' => 'Soil moisture',
                'domain' => 'hydrology',
                'canonical_unit' => '%',
                'data_type' => 'numeric',
                'measurement_characteristic' => 'measured',
                'is_platform_processed' => false,
                'input_requirements' => [
                    'source_note' => 'Demo raw register is scaled by 0.1 for operational display.',
                    'min_value' => 0,
                    'max_value' => 100,
                ],
                'status' => 'active',
            ]
        );

        $moistureSensor = Sensor::updateOrCreate(
            ['sensor_code' => 'SENSOR-LEMBAB'],
            [
                'workspace_id' => $workspace->id,
                'monitoring_station_id' => $station->id,
                'data_logger_id' => $logger->id,
                'warning_station_id' => null,
                'mst_prefix_id' => $moisturePrefix->id,
                'slave_id' => '4',
                'address' => '0',
                'function_code' => 'FC03',
                'quantity' => 1,
                'poll_interval_ms' => 1000,
                'type' => 'soil_moisture',
                'parameter' => 'Soil Moisture',
                'data_type' => 'uint16',
                'scale_factor' => 0.1,
                'offset' => 0,
                'unit' => '%',
                'reading_method' => 'Absolute',
                'threshold' => '35',
                'rule' => 'AWAS when mapped SoilMoisture >= 35%',
                'alert_level' => 'Normal',
                'status' => 'Normal',
                'last_seen_at' => now(),
            ]
        );

        SensorMappingProfile::where('sensor_id', $moistureSensor->id)
            ->where('profile_code', '!=', 'MAP-SENSOR-LEMBAB-SOILMOISTURE')
            ->delete();

        SensorMappingProfile::updateOrCreate(
            ['profile_code' => 'MAP-SENSOR-LEMBAB-SOILMOISTURE'],
            [
                'sensor_id' => $moistureSensor->id,
                'manufacturer' => 'Rika Sensor',
                'device_model' => 'Soil Moisture Demo',
                'communication_path' => 'RS485 Modbus RTU',
                'slave_id' => 4,
                'source_parameter' => 'Soil moisture raw register',
                'source_unit' => 'raw',
                'register_address' => '0',
                'function_code' => 'FC03',
                'value_type' => 'uint16',
                'data_length' => 1,
                'byte_order' => null,
                'scale_factor' => 0.1,
                'offset' => 0,
                'canonical_parameter_id' => $moistureParameter->id,
                'value_origin' => 'direct_measurement',
                'status' => 'active',
            ]
        );

        ConnectivityConfig::updateOrCreate(
            ['connectivity_code' => 'SERIAL-REDNODE-BLIIOT-011'],
            [
                'data_logger_id' => $logger->id,
                'communication_type' => 'Serial',
                'protocol' => 'Modbus RTU',
                'host_or_endpoint' => '/dev/ttyAS2',
                'serial_port' => '/dev/ttyAS2',
                'baud_rate' => 9600,
                'data_bits' => 8,
                'parity' => 'none',
                'stop_bits' => 1,
                'topic_or_api_path' => 'Pin 5-6 / /dev/ttyAS2',
                'pin_mapping' => 'Pin 5-6 / /dev/ttyAS2',
                'monitored_sensor_ids' => [$sensor->id, $moistureSensor->id],
                'rednode_poll_interval_ms' => 1000,
                'connectivity_status' => 'Online',
                'connection_state' => 'connected',
                'uplink_state' => 'uplink',
                'last_seen_at' => now(),
                'last_connected_at' => now(),
                'runtime_state' => ['source' => 'rika-demo-seeder', 'monitoring_enabled' => true],
            ]
        );

        ConnectivityConfig::where('connectivity_code', 'SERIAL-REDNODE-BLIIOT-011-TTYAS3')->delete();

        foreach ($this->parameters() as [$field, $source, $unit, $address, $type, $length, $byteOrder]) {
            $parameter = CanonicalParameter::firstOrCreate(
                ['field_identity' => $field],
                ['domain' => 'meteorology', 'canonical_unit' => $unit, 'status' => 'active']
            );

            SensorMappingProfile::updateOrCreate(
                ['profile_code' => 'MAP-RIKA-CUACA-' . strtoupper(str_replace(['.', ' '], ['', '-'], $field))],
                [
                    'sensor_id' => $sensor->id,
                    'manufacturer' => 'Rika Sensor',
                    'device_model' => 'RK900-11',
                    'communication_path' => 'RS485 Modbus RTU',
                    'slave_id' => 1,
                    'source_parameter' => $source,
                    'source_unit' => $unit,
                    'register_address' => $address,
                    'function_code' => 'FC03',
                    'value_type' => $type,
                    'data_length' => $length,
                    'byte_order' => $byteOrder,
                    'scale_factor' => 1,
                    'offset' => 0,
                    'canonical_parameter_id' => $parameter->id,
                    'value_origin' => 'direct_measurement',
                    'status' => 'active',
                ]
            );
        }

        $registers = [151, 356, 3024, 16400, 39980, 16900, 45771, 16986, 13801, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 33019, 15618, 6502, 15458, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        TelemetryReading::create([
            'sensor_id' => $sensor->id,
            'data_logger_id' => $logger->id,
            'value' => 'WindDirection 356.00 deg, WindSpeed 0.00 m/s, Temperature -0.00 C, Humidity -0.00 %, Pressure 0.00 hPa, Rainfall 0.00 mm, PM25 0.00 ug/m3, Illumination 0.00 lux, Irradiance 0.00 W/m2, Altitude 0.00 m, PM10 0.00 ug/m3',
            'raw_value' => '356',
            'numeric_value' => 356,
            'registers' => $registers,
            'parameter_values' => [],
            'alert_level' => 'Normal',
            'status' => 'Normal',
            'received_at' => now(),
        ]);

        TelemetryReading::create([
            'sensor_id' => $moistureSensor->id,
            'data_logger_id' => $logger->id,
            'value' => '39.40',
            'raw_value' => '394',
            'numeric_value' => 39.4,
            'registers' => [394],
            'parameter_values' => [],
            'alert_level' => 'Awas',
            'status' => 'Awas',
            'received_at' => now(),
        ]);

        Sensor::whereNotIn('sensor_code', ['RIKA-CUACA', 'SENSOR-LEMBAB'])->get()->each(function (Sensor $staleSensor) {
            TelemetryReading::where('sensor_id', $staleSensor->id)->delete();
            $staleSensor->mappingProfiles()->delete();
            $staleSensor->delete();
        });

        DataLogger::where('logger_code', '!=', 'REDNODE-BLIIOT-011')->get()->each(function (DataLogger $staleLogger) {
            ConnectivityConfig::where('data_logger_id', $staleLogger->id)->delete();
            $staleLogger->delete();
        });

        $latestRows = collect(app(SentinelRuntimeReadService::class)->latestReadings($station)['readings']);

        foreach ([$sensor, $moistureSensor] as $runtimeSensor) {
            $rows = $latestRows
                ->where('sensor_id', $runtimeSensor->id)
                ->values()
                ->map(fn (array $row) => [
                'parameter' => $row['parameter'],
                'label' => $row['parameter'],
                'value' => $row['value'],
                'unit' => $row['unit'],
                'value_text' => trim((string) $row['value'] . ' ' . (string) $row['unit']),
                'fresh' => $row['fresh'],
                'raw' => $row['raw'] ?? null,
                'register_address' => $row['register_address'] ?? null,
                'register_index' => $row['register_index'] ?? null,
                'registers' => $row['registers'] ?? null,
                'value_type' => $row['value_type'] ?? null,
                'data_length' => $row['data_length'] ?? null,
                'byte_order' => $row['byte_order'] ?? null,
                'scale_factor' => $row['scale_factor'] ?? null,
                'offset' => $row['offset'] ?? null,
            ])
                ->all();

            TelemetryReading::where('sensor_id', $runtimeSensor->id)
                ->latest('received_at')
                ->first()
                ?->update(['parameter_values' => $rows]);
        }

        $rainfall = $sensor->mappingProfiles()
            ->whereHas('canonicalParameter', fn ($query) => $query->where('field_identity', 'Rainfall'))
            ->first();

        HydrometHazardClassification::where('classification_code', 'HZ-DEMO-WL-01')->update([
            'sensor_id' => $sensor->id,
            'canonical_parameter_id' => $rainfall?->canonical_parameter_id,
            'parameter' => 'Rainfall',
            'status' => 'active',
        ]);

        $relationship = HydrometEwsRelationship::where('project_id', $project->id)->first();
        HydrometHazardClassification::updateOrCreate(
            ['classification_code' => 'HZ-DEMO-SOILMOISTURE-01'],
            [
                'project_id' => $project->id,
                'hydromet_ews_relationship_id' => $relationship?->id,
                'corridor_id' => $station->corridor_id,
                'monitoring_station_id' => $station->id,
                'sensor_id' => $moistureSensor->id,
                'canonical_parameter_id' => $moistureParameter->id,
                'parameter' => 'SoilMoisture',
                'reading_method' => HydrometHazardClassification::METHOD_ABSOLUTE,
                'threshold_config' => [
                    'comparison' => '>=',
                    'basis' => 'Mapped SoilMoisture value from raw register multiplied by 0.1.',
                ],
                'hazard_levels' => [
                    'WASPADA' => ['level' => 'WASPADA', 'threshold' => 25],
                    'SIAGA' => ['level' => 'SIAGA', 'threshold' => 30],
                    'AWAS' => ['level' => 'AWAS', 'threshold' => 35],
                ],
                'unresolved_business_rules' => [
                    'Persistence and de-escalation windows must be confirmed by hydromet authority.',
                ],
                'evaluation_engine' => 'configuration_only',
                'status' => 'active',
            ]
        );
    }

    private function parameters(): array
    {
        return [
            ['WindDirection', 'Wind direction', 'deg', '40002', 'uint16', 1, null],
            ['WindSpeed', 'Wind speed', 'm/s', '40003', 'float32', 2, 'CDAB'],
            ['Temperature', 'Atmospheric temperature', 'C', '40005', 'float32', 2, 'CDAB'],
            ['Humidity', 'Atmospheric humidity', '%', '40007', 'float32', 2, 'CDAB'],
            ['Pressure', 'Atmospheric pressure', 'hPa', '40009', 'float32', 2, 'CDAB'],
            ['Rainfall', 'Rainfall', 'mm', '40013', 'float32', 2, 'CDAB'],
            ['PM25', 'Dust concentration (PM2.5)', 'ug/m3', '40026', 'float32', 2, 'CDAB'],
            ['Illumination', 'Illumination', 'lux', '40030', 'float32', 2, 'CDAB'],
            ['Irradiance', 'Radiation', 'W/m2', '40034', 'float32', 2, 'CDAB'],
            ['Altitude', 'Altitude', 'm', '40038', 'float32', 2, 'CDAB'],
            ['PM10', 'PM10', 'ug/m3', '40048', 'float32', 2, 'CDAB'],
        ];
    }
}
