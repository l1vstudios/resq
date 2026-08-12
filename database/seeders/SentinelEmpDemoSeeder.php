<?php

namespace Database\Seeders;

use App\Models\CanonicalParameter;
use App\Models\Client;
use App\Models\ConnectivityConfig;
use App\Models\CorridorMonitoring;
use App\Models\DataLogger;
use App\Models\GeospatialWorkspace;
use App\Models\HydrometEwsRelationship;
use App\Models\HydrometHazardClassification;
use App\Models\HydrometWdamConfig;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\ReferencePoint;
use App\Models\ReferenceRoute;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use App\Models\SentinelNotification;
use App\Models\SpatialInformationLayer;
use App\Models\StationFunctionConfiguration;
use App\Models\StationSpatialReference;
use App\Models\TelemetryReading;
use App\Models\User;
use App\Models\WarningStation;
use App\Models\WarningStationDevice;
use App\Models\WarningStationDeviceHeartbeat;
use App\Models\WarningStationTelemetryConfig;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SentinelEmpDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RbacSeeder::class);

        $demoNow = Carbon::parse('2026-08-11 08:00:00');
        $semeruCorridorPath = [
            ['lat' => -8.1080, 'lng' => 112.9220],
            ['lat' => -8.1378, 'lng' => 112.9467],
            ['lat' => -8.1724, 'lng' => 112.9716],
            ['lat' => -8.2052, 'lng' => 112.9940],
            ['lat' => -8.2390, 'lng' => 113.0185],
        ];

        $sentinelAdminRole = Role::where('name', 'SentinelAdmin')->firstOrFail();
        User::updateOrCreate(
            ['email' => 'sentinel.admin@resq.local'],
            [
                'name' => 'Demo Sentinel Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => $demoNow,
                'dob' => '2000-01-01',
                'avatar' => 'images/avatar-1.jpg',
                'type' => 'sentinel',
                'client_id' => null,
                'status' => 'active',
            ]
        );

        User::where('type', 'sentinel')->get()->each(function (User $user) use ($sentinelAdminRole) {
            $user->forceFill(['status' => 'active'])->save();
            $user->assignRole($sentinelAdminRole);
        });

        $clientAdminRole = Role::where('name', 'ClientAdmin')->firstOrFail();
        $clientOperatorRole = Role::where('name', 'ClientOperator')->firstOrFail();

        $client = Client::updateOrCreate(
            ['client_code' => 'DEMO-CLIENT'],
            [
                'name' => 'Demo Client Sentinel EMP',
                'contact_name' => 'Demo PIC',
                'contact_email' => 'client.demo@resq.local',
                'contact_phone' => '+628000000001',
                'status' => 'active',
                'max_users' => 5,
                'max_projects' => 3,
            ]
        );

        $clientAdmin = $this->clientUser($client, $clientAdminRole, [
            'name' => 'Demo Client Admin',
            'email' => 'client.admin@resq.local',
        ]);
        $this->clientUser($client, $clientOperatorRole, [
            'name' => 'Demo Client Operator',
            'email' => 'client.operator@resq.local',
        ]);

        $project = \App\Models\Project::updateOrCreate(
            ['project_code' => 'EMP-DEMO-001'],
            [
                'name' => 'Sentinal Project',
                'owner' => 'Sentinel Demo',
                'client_id' => $client->id,
                'project_date' => now()->toDateString(),
                'status' => 'Active',
            ]
        );

        $workspace = GeospatialWorkspace::updateOrCreate(
            ['workspace_code' => 'GWS-EMP-DEMO'],
            [
                'project_id' => $project->id,
                'name' => 'Semeru Geospatial Workspace',
                'hazard' => 'Hydromet',
                'province' => 'Jawa Timur',
                'city' => 'Lumajang',
                'beneficiaries' => 12000,
                'latitude' => -8.1724,
                'longitude' => 112.9716,
                'status' => 'Normal',
                'basemap_provider' => 'OpenStreetMap',
                'default_zoom' => 12,
            ]
        );

        SpatialInformationLayer::updateOrCreate(
            ['layer_code' => 'LAYER-DEMO-FLOODPLAIN'],
            [
                'project_id' => $project->id,
                'workspace_id' => $workspace->id,
                'name' => 'Semeru Lahar Information Layer',
                'layer_type' => 'GeoJSON',
                'layer_payload' => [
                    'type' => 'FeatureCollection',
                    'features' => [],
                ],
                'style_color' => '#2563eb',
                'visible_by_default' => true,
                'sort_order' => 1,
                'status' => 'Active',
            ]
        );

        $route = ReferenceRoute::updateOrCreate(
            ['route_code' => 'RR-DEMO-RIVER'],
            [
                'project_id' => $project->id,
                'workspace_id' => $workspace->id,
                'name' => 'Semeru Lahar Reference Route',
                'route_type' => 'lahar_corridor',
                'path_coordinates' => $semeruCorridorPath,
                'status' => 'Active',
                'notes' => 'Demo reference route from Semeru summit toward downstream monitoring points for CFPE binding.',
            ]
        );

        $corridor = CorridorMonitoring::updateOrCreate(
            ['corridor_code' => 'COR-DEMO-01'],
            [
                'project_id' => $project->id,
                'workspace_id' => $workspace->id,
                'reference_route_id' => $route->id,
                'name' => 'Semeru Lahar Corridor',
                'path_coordinates' => $route->path_coordinates,
                'status' => 'Active',
                'status_metadata' => ['hazard_state' => 'WASPADA'],
                'notes' => 'Operational corridor from Semeru source area to downstream warning response points.',
            ]
        );

        $bm = ReferencePoint::updateOrCreate(
            ['point_code' => 'BM-DEMO-01'],
            [
                'project_id' => $project->id,
                'workspace_id' => $workspace->id,
                'corridor_id' => $corridor->id,
                'reference_route_id' => $route->id,
                'name' => 'Semeru Reference BM 01',
                'point_type' => 'BM',
                'coordinate' => '-8.1080,112.9220',
                'latitude' => -8.1080,
                'longitude' => 112.9220,
                'status' => 'Active',
                'notes' => 'Reference BM near Semeru source area for CFPE demo.',
            ]
        );

        $station = MonitoringStation::updateOrCreate(
            ['station_code' => 'MS-DEMO-01'],
            [
                'project_id' => $project->id,
                'workspace_id' => $workspace->id,
                'corridor_id' => $corridor->id,
                'name' => 'Demo Monitoring Station 01',
                'station_type' => 'hydromet_monitoring',
                'coordinate' => '-8.1724,112.9716',
                'latitude' => -8.1724,
                'longitude' => 112.9716,
                'logger_status' => 'Active',
                'connectivity_status' => 'Online',
                'registration_status' => 'registered',
                'registered_at' => $demoNow->copy()->subDays(20),
                'registered_by_user_id' => User::where('type', 'sentinel')->value('id'),
                'status' => 'Normal',
                'service_status' => 'Active',
                'service_period_start' => $demoNow->copy()->subMonth()->toDateString(),
                'service_period_end' => $demoNow->copy()->addYear()->toDateString(),
                'entitlement' => 'standard',
                'package_status' => 'Active',
            ]
        );

        StationSpatialReference::updateOrCreate(
            [
                'project_id' => $project->id,
                'monitoring_station_id' => $station->id,
                'placement_role' => 'primary_monitoring',
            ],
            [
                'workspace_id' => $workspace->id,
                'corridor_id' => $corridor->id,
                'reference_route_id' => $route->id,
                'reference_point_id' => $bm->id,
                'station_offset' => 'chainage:125.75;offset:3.5m centerline',
                'status' => 'Active',
            ]
        );

        foreach ([
            ['MS-SEMERU-UPPER', 'Semeru Upper Monitoring Station', '-8.1378,112.9467', -8.1378, 112.9467, 'chainage:1400;upper corridor monitoring point'],
            ['MS-SEMERU-DOWN', 'Semeru Downstream Monitoring Station', '-8.2390,113.0185', -8.2390, 113.0185, 'chainage:4600;downstream corridor monitoring point'],
        ] as [$code, $name, $coordinate, $latitude, $longitude, $offset]) {
            $visualStation = MonitoringStation::updateOrCreate(
                ['station_code' => $code],
                [
                    'project_id' => $project->id,
                    'workspace_id' => $workspace->id,
                    'corridor_id' => $corridor->id,
                    'name' => $name,
                    'station_type' => 'hydromet_monitoring',
                    'coordinate' => $coordinate,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'logger_status' => 'Registered',
                    'connectivity_status' => 'Pending',
                    'registration_status' => 'registered',
                    'registered_at' => $demoNow->copy()->subDays(18),
                    'registered_by_user_id' => User::where('type', 'sentinel')->value('id'),
                    'status' => 'Normal',
                    'service_status' => 'Active',
                    'service_period_start' => $demoNow->copy()->subMonth()->toDateString(),
                    'service_period_end' => $demoNow->copy()->addYear()->toDateString(),
                    'entitlement' => 'standard',
                    'package_status' => 'Active',
                ]
            );

            StationSpatialReference::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'monitoring_station_id' => $visualStation->id,
                    'placement_role' => 'corridor_monitoring',
                ],
                [
                    'workspace_id' => $workspace->id,
                    'corridor_id' => $corridor->id,
                    'reference_route_id' => $route->id,
                    'reference_point_id' => $bm->id,
                    'station_offset' => $offset,
                    'status' => 'Active',
                ]
            );
        }

        $logger = DataLogger::updateOrCreate(
            ['logger_code' => 'DL-DEMO-01'],
            [
                'monitoring_station_id' => $station->id,
                'serial_number' => 'DL-DEMO-SN-01',
                'logger_model' => 'Demo MQTT Logger',
                'vendor' => 'RESQ',
                'firmware_version' => '1.0.0',
                'device_label' => 'Demo Logger 01',
                'logger_status' => 'Active',
            ]
        );

        ConnectivityConfig::updateOrCreate(
            ['connectivity_code' => 'MQTT-DEMO-01'],
            [
                'data_logger_id' => $logger->id,
                'communication_type' => 'MQTT',
                'protocol' => 'MQTT',
                'host_or_endpoint' => 'mqtt://demo-broker.local',
                'port' => 1883,
                'topic_or_api_path' => 'sentinel/demo/ms-demo-01/uplink',
                'gateway_id' => 'GW-DEMO-01',
                'connectivity_status' => 'Online',
                'connection_state' => 'connected',
                'uplink_state' => 'uplink',
                'last_seen_at' => $demoNow,
                'last_connected_at' => $demoNow->copy()->subMinutes(5),
                'runtime_state' => ['monitoring_enabled' => true],
            ]
        );

        $rainfall = $this->sensorWithMapping($workspace, $station, $logger, 'SNS-DEMO-RAIN', 'rainfall', 'Rainfall', 'meteorology', 'mm', '12.5');
        $level = $this->sensorWithMapping($workspace, $station, $logger, 'SNS-DEMO-WL', 'water_level', 'WaterLevel', 'hydrology', 'm', '2.40');
        $velocity = $this->sensorWithMapping($workspace, $station, $logger, 'SNS-DEMO-VEL', 'water_velocity', 'WaterVelocity', 'hydrology', 'm/s', '0.70');

        collect([$rainfall, $level, $velocity])->each(function (Sensor $sensor, int $idx) use ($logger, $demoNow) {
            TelemetryReading::updateOrCreate(
                [
                    'sensor_id' => $sensor->id,
                    'received_at' => $demoNow->copy()->subMinutes(30 - ($idx * 5)),
                ],
                [
                    'data_logger_id' => $logger->id,
                    'value' => $sensor->value,
                    'numeric_value' => $sensor->value,
                    'parameter_values' => [[
                        'parameter' => $sensor->parameter,
                        'value' => $sensor->value,
                        'unit' => $sensor->unit,
                    ]],
                    'alert_level' => $sensor->sensor_code === 'SNS-DEMO-WL' ? 'Waspada' : 'Normal',
                    'status' => $sensor->sensor_code === 'SNS-DEMO-WL' ? 'Waspada' : 'Normal',
                ]
            );
        });

        $warningStation = WarningStation::updateOrCreate(
            ['station_code' => 'WS-DEMO-01'],
            [
                'project_id' => $project->id,
                'workspace_id' => $workspace->id,
                'monitoring_station_id' => $station->id,
                'name' => 'Demo Warning Station 01',
                'zone_id' => 'ZONE-DEMO-01',
                'administrative_location' => 'Lumajang Downstream Demo Zone',
                'coordinate' => '-8.2052,112.9940',
                'latitude' => -8.2052,
                'longitude' => 112.9940,
                'controller_id' => 'WSCP-DEMO-01',
                'controller_model' => 'Demo WSCP',
                'controller_vendor' => 'RESQ',
                'controller_status' => 'Standby',
                'registration_status' => 'registered',
                'registered_at' => $demoNow->copy()->subDays(15),
                'registered_by_user_id' => User::where('type', 'sentinel')->value('id'),
                'output_devices' => ['WSCP', 'ASCP', 'Siren', 'Beacon'],
                'status' => 'Normal',
                'service_status' => 'Active',
                'service_period_start' => $demoNow->copy()->subMonth()->toDateString(),
                'service_period_end' => $demoNow->copy()->addYear()->toDateString(),
                'entitlement' => 'standard',
                'package_status' => 'Active',
                'public_warning_enabled' => true,
                'ack_response' => 'manual_ack_required',
                'notes' => 'Demo warning station. Low-level ASCP behavior is not configured here.',
            ]
        );

        StationSpatialReference::updateOrCreate(
            [
                'project_id' => $project->id,
                'warning_station_id' => $warningStation->id,
                'placement_role' => 'downstream_warning',
            ],
            [
                'workspace_id' => $workspace->id,
                'corridor_id' => $corridor->id,
                'reference_route_id' => $route->id,
                'reference_point_id' => $bm->id,
                'monitoring_station_id' => null,
                'station_offset' => 'chainage:3100;downstream warning response point',
                'status' => 'Active',
            ]
        );

        $downstreamWarningStation = WarningStation::updateOrCreate(
            ['station_code' => 'WS-SEMERU-DOWN'],
            [
                'project_id' => $project->id,
                'workspace_id' => $workspace->id,
                'monitoring_station_id' => $station->id,
                'name' => 'Semeru Downstream Warning Station',
                'zone_id' => 'ZONE-SEMERU-DOWN',
                'administrative_location' => 'Lumajang Downstream Warning Zone',
                'coordinate' => '-8.2410,113.0200',
                'latitude' => -8.2410,
                'longitude' => 113.0200,
                'controller_id' => 'WSCP-SEMERU-DOWN',
                'controller_model' => 'Demo WSCP',
                'controller_vendor' => 'RESQ',
                'controller_status' => 'Standby',
                'registration_status' => 'registered',
                'registered_at' => $demoNow->copy()->subDays(15),
                'registered_by_user_id' => User::where('type', 'sentinel')->value('id'),
                'output_devices' => ['WSCP', 'Siren', 'Beacon'],
                'status' => 'Normal',
                'service_status' => 'Active',
                'service_period_start' => $demoNow->copy()->subMonth()->toDateString(),
                'service_period_end' => $demoNow->copy()->addYear()->toDateString(),
                'entitlement' => 'standard',
                'package_status' => 'Active',
                'public_warning_enabled' => true,
                'ack_response' => 'manual_ack_required',
                'notes' => 'Demo downstream warning station. Low-level output behavior remains outside Sentinel EMP.',
            ]
        );

        StationSpatialReference::updateOrCreate(
            [
                'project_id' => $project->id,
                'warning_station_id' => $downstreamWarningStation->id,
                'placement_role' => 'downstream_warning',
            ],
            [
                'workspace_id' => $workspace->id,
                'corridor_id' => $corridor->id,
                'reference_route_id' => $route->id,
                'reference_point_id' => $bm->id,
                'monitoring_station_id' => null,
                'station_offset' => 'chainage:4600;downstream warning response point',
                'status' => 'Active',
            ]
        );

        WarningStationTelemetryConfig::updateOrCreate(
            ['config_code' => 'WSTC-DEMO-01'],
            [
                'project_id' => $project->id,
                'warning_station_id' => $warningStation->id,
                'broker_config_ref' => 'shared-demo-mqtt',
                'protocol' => 'MQTT',
                'host_or_endpoint' => 'mqtt://demo-broker.local',
                'port' => 1883,
                'topic' => 'sentinel/demo/ws-demo-01/heartbeat',
                'qos' => 1,
                'retain' => false,
                'credential_ref' => 'secret:demo-warning-mqtt',
                'connection_status' => 'connected',
                'last_connected_at' => $demoNow->copy()->subMinutes(8),
                'last_seen_at' => $demoNow->copy()->subMinutes(2),
            ]
        );

        foreach ([
            ['WSCP-DEMO-01', WarningStationDevice::TYPE_WSCP, 'Demo WSCP'],
            ['ASCP-DEMO-01', WarningStationDevice::TYPE_ASCP, 'Demo ASCP'],
            ['SIREN-DEMO-01', WarningStationDevice::TYPE_SIREN, 'Demo Siren'],
            ['BEACON-DEMO-01', WarningStationDevice::TYPE_BEACON, 'Demo Beacon'],
        ] as [$code, $type, $name]) {
            $device = WarningStationDevice::updateOrCreate(
                ['device_code' => $code],
                [
                    'project_id' => $project->id,
                    'warning_station_id' => $warningStation->id,
                    'device_type' => $type,
                    'name' => $name,
                    'vendor' => 'RESQ',
                    'model' => 'Demo Output',
                    'serial_number' => $code.'-SN',
                    'expected' => true,
                    'availability_state' => 'available',
                    'health_state' => 'ok',
                    'last_heartbeat_at' => $demoNow->copy()->subMinutes(2),
                    'health_payload' => ['battery' => 'normal', 'link' => 'ok'],
                    'status' => 'registered',
                ]
            );

            WarningStationDeviceHeartbeat::updateOrCreate(
                [
                    'warning_station_device_id' => $device->id,
                    'received_at' => $demoNow->copy()->subMinutes(2),
                ],
                [
                    'project_id' => $project->id,
                    'warning_station_id' => $warningStation->id,
                    'device_code' => $code,
                    'device_type' => $type,
                    'availability_state' => 'available',
                    'health_state' => 'ok',
                    'observed_at' => $demoNow->copy()->subMinutes(3),
                    'health_payload' => ['source' => 'demo-seeder'],
                ]
            );
        }

        $relationship = HydrometEwsRelationship::updateOrCreate(
            ['relationship_code' => 'EWS-DEMO-01'],
            [
                'project_id' => $project->id,
                'corridor_id' => $corridor->id,
                'monitoring_station_id' => $station->id,
                'warning_station_id' => $warningStation->id,
                'name' => 'Demo Hydromet EWS Relationship',
                'status' => 'active',
                'notes' => 'Corridor + Monitoring Station + Warning Station demo relation.',
            ]
        );

        HydrometHazardClassification::updateOrCreate(
            ['classification_code' => 'HZ-DEMO-WL-01'],
            [
                'project_id' => $project->id,
                'hydromet_ews_relationship_id' => $relationship->id,
                'corridor_id' => $corridor->id,
                'monitoring_station_id' => $station->id,
                'sensor_id' => $level->id,
                'canonical_parameter_id' => $level->mappingProfile?->canonical_parameter_id,
                'parameter' => 'WaterLevel',
                'reading_method' => HydrometHazardClassification::METHOD_ABSOLUTE,
                'threshold_config' => ['comparison' => 'configuration_only'],
                'hazard_levels' => [
                    'WASPADA' => ['level' => 'WASPADA', 'threshold' => 2.0],
                    'SIAGA' => ['level' => 'SIAGA', 'threshold' => 3.0],
                    'AWAS' => ['level' => 'AWAS', 'threshold' => 4.0],
                ],
                'unresolved_business_rules' => [
                    'Comparison direction and persistence rules must be confirmed by hydromet authority.',
                ],
                'evaluation_engine' => 'configuration_only',
                'status' => 'active',
            ]
        );

        HydrometWdamConfig::updateOrCreate(
            ['wdam_code' => 'WDAM-DEMO-01'],
            [
                'project_id' => $project->id,
                'hydromet_ews_relationship_id' => $relationship->id,
                'warning_station_id' => $warningStation->id,
                'dashboard_notification_enabled' => true,
                'registered_recipients' => [
                    ['name' => 'Demo Operator', 'channel' => 'dashboard'],
                ],
                'sms_enabled' => false,
                'whatsapp_enabled' => false,
                'warning_station_assignment_enabled' => true,
                'automatic_activation_enabled' => false,
                'authority_method' => 'manual_authority',
                'status' => 'active',
                'notes' => 'Provider credentials not selected in demo.',
            ]
        );

        foreach ([
            ['TDE', 'Moving Average', ['data_window' => ['value' => 6, 'unit' => 'hours']]],
            ['Discharge', 'Absolute', ['cross_sectional_area' => 12.5, 'manning_n' => 0.031, 'coefficient_cd' => 0.72, 'unit' => 'm3/s', 'calculation_runtime' => 'backend_only']],
            ['CFPE', 'Probability', ['reference_route_id' => $route->id, 'reference_bm_id' => $bm->id, 'offset_direction' => 'centerline', 'offset_distance' => 3.5, 'station_ground_zero_chainage' => 125.75, 'uncertainty_factor' => 0.2, 'calculation_runtime' => 'backend_only']],
        ] as [$function, $method, $config]) {
            StationFunctionConfiguration::updateOrCreate(
                ['monitoring_station_id' => $station->id, 'function_name' => $function],
                [
                    'project_id' => $project->id,
                    'reading_method' => $method,
                    'configuration' => $config,
                    'validation_state' => $function === 'CFPE' ? 'validated' : 'not_validated',
                    'validated_at' => $function === 'CFPE' ? $demoNow : null,
                    'activated_at' => $function === 'CFPE' ? $demoNow : null,
                    'unresolved_analytical_rules' => [
                        $function.' calculation formula is intentionally not implemented in demo seed.',
                    ],
                    'status' => $function === 'CFPE' ? 'active' : 'draft',
                ]
            );
        }

        SentinelNotification::updateOrCreate(
            [
                'client_id' => $client->id,
                'project_id' => $project->id,
                'event_type' => 'Waspada',
                'title' => 'Demo Waspada WaterLevel',
            ],
            [
                'corridor_id' => $corridor->id,
                'monitoring_station_id' => $station->id,
                'warning_station_id' => $warningStation->id,
                'category' => SentinelNotification::CATEGORY_OPERATIONAL,
                'body' => 'Demo notification for WaterLevel Waspada state.',
                'source_context' => ['parameter' => 'WaterLevel', 'value' => 2.4],
                'occurred_at' => $demoNow->copy()->subMinutes(20),
                'read_at' => null,
            ]
        );

        $clientAdmin->projects()->syncWithoutDetaching([
            $project->id => ['access_level' => 'manager'],
        ]);

        $this->command?->info('Sentinel EMP demo data seeded. Sentinel users can open /platform-operations.');
        $this->command?->info('Demo sentinel login: sentinel.admin@resq.local / password123');
        $this->command?->info('Demo client login: client.admin@resq.local / password123');
    }

    private function clientUser(Client $client, Role $role, array $data): User
    {
        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'dob' => '2000-01-01',
                'avatar' => 'images/avatar-1.jpg',
                'type' => 'client',
                'client_id' => $client->id,
                'status' => 'active',
            ]
        );
        $user->assignRole($role);

        return $user;
    }

    private function sensorWithMapping(
        GeospatialWorkspace $workspace,
        MonitoringStation $station,
        DataLogger $logger,
        string $sensorCode,
        string $type,
        string $field,
        string $domain,
        string $unit,
        string $value
    ): Sensor {
        $prefix = MstPrefix::firstOrCreate(
            ['prefix_code' => 'DEMO'],
            ['name' => 'Demo Modbus Prefix', 'status' => 'Active']
        );

        $sensor = Sensor::updateOrCreate(
            ['sensor_code' => $sensorCode],
            [
                'workspace_id' => $workspace->id,
                'monitoring_station_id' => $station->id,
                'data_logger_id' => $logger->id,
                'mst_prefix_id' => $prefix->id,
                'slave_id' => match ($sensorCode) {
                    'SNS-DEMO-RAIN' => '1',
                    'SNS-DEMO-WL' => '2',
                    default => '3',
                },
                'address' => match ($sensorCode) {
                    'SNS-DEMO-RAIN' => '40001',
                    'SNS-DEMO-WL' => '40003',
                    default => '40005',
                },
                'function_code' => 'FC03',
                'quantity' => 2,
                'poll_interval_ms' => 60000,
                'type' => $type,
                'parameter' => $field,
                'value' => $value,
                'data_type' => 'float32',
                'scale_factor' => 1,
                'offset' => 0,
                'unit' => $unit,
                'reading_method' => 'Absolute',
                'alert_level' => $field === 'WaterLevel' ? 'Waspada' : 'Normal',
                'status' => $field === 'WaterLevel' ? 'Waspada' : 'Active',
                'last_seen_at' => Carbon::parse('2026-08-11 07:55:00'),
            ]
        );

        $canonical = CanonicalParameter::firstOrCreate(
            ['field_identity' => $field],
            [
                'definition' => $field,
                'domain' => $domain,
                'canonical_unit' => $unit,
                'data_type' => 'numeric',
                'measurement_characteristic' => 'measured',
                'is_platform_processed' => false,
                'status' => 'active',
            ]
        );

        SensorMappingProfile::updateOrCreate(
            ['profile_code' => 'MAP-'.$sensorCode],
            [
                'sensor_id' => $sensor->id,
                'source_parameter' => $field,
                'source_unit' => $unit,
                'register_address' => $sensor->address,
                'function_code' => $sensor->function_code,
                'value_type' => 'float32',
                'canonical_parameter_id' => $canonical->id,
                'value_origin' => 'direct_measurement',
                'status' => 'active',
            ]
        );

        return $sensor->fresh('mappingProfile');
    }
}
