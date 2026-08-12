<?php

namespace Tests\Feature;

use App\Models\CanonicalParameter;
use App\Models\Client;
use App\Models\CorridorMonitoring;
use App\Models\GeospatialWorkspace;
use App\Models\HydrometEwsRelationship;
use App\Models\HydrometHazardClassification;
use App\Models\MonitoringStation;
use App\Models\MstPrefix;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\User;
use App\Models\WarningStation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HydrometEwsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private Role $clientRole;
    private Role $sentinelRole;
    private Client $clientA;
    private Client $clientB;
    private Project $projectA;
    private Project $projectB;
    private GeospatialWorkspace $workspaceA;
    private GeospatialWorkspace $workspaceB;
    private CorridorMonitoring $corridorA;
    private CorridorMonitoring $corridorB;
    private MonitoringStation $monitoringA;
    private MonitoringStation $monitoringB;
    private WarningStation $warningA;
    private WarningStation $warningB;
    private Sensor $sensorA;
    private CanonicalParameter $parameter;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'project.create', 'resource' => 'project', 'action' => 'create']);
        Permission::create(['name' => 'project.edit', 'resource' => 'project', 'action' => 'edit']);
        Permission::create(['name' => 'project.delete', 'resource' => 'project', 'action' => 'delete']);

        $this->clientRole = Role::create(['name' => 'ClientAdmin', 'type' => 'system']);
        $this->sentinelRole = Role::create(['name' => 'SentinelHydromet', 'type' => 'system']);

        $this->clientA = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $this->clientB = Client::create(['client_code' => 'CLIENT-B', 'name' => 'Client B']);
        $this->projectA = Project::create(['project_code' => 'PRJ-A', 'name' => 'Project A', 'client_id' => $this->clientA->id, 'status' => 'Active']);
        $this->projectB = Project::create(['project_code' => 'PRJ-B', 'name' => 'Project B', 'client_id' => $this->clientB->id, 'status' => 'Active']);
        $this->workspaceA = GeospatialWorkspace::create(['project_id' => $this->projectA->id, 'workspace_code' => 'GWS-A', 'name' => 'Workspace A', 'province' => 'A', 'status' => 'Normal']);
        $this->workspaceB = GeospatialWorkspace::create(['project_id' => $this->projectB->id, 'workspace_code' => 'GWS-B', 'name' => 'Workspace B', 'province' => 'B', 'status' => 'Normal']);
        $this->corridorA = $this->corridor($this->projectA, $this->workspaceA, 'COR-A');
        $this->corridorB = $this->corridor($this->projectB, $this->workspaceB, 'COR-B');
        $this->monitoringA = $this->monitoringStation($this->projectA, $this->workspaceA, 'MS-A', $this->corridorA);
        $this->monitoringB = $this->monitoringStation($this->projectB, $this->workspaceB, 'MS-B', $this->corridorB);
        $this->warningA = $this->warningStation($this->projectA, $this->workspaceA, 'WS-A', $this->monitoringA);
        $this->warningB = $this->warningStation($this->projectB, $this->workspaceB, 'WS-B', $this->monitoringB);
        $this->parameter = CanonicalParameter::create([
            'field_identity' => 'WaterLevel',
            'domain' => 'hydrology',
            'canonical_unit' => 'm',
            'status' => 'active',
        ]);
        $this->sensorA = $this->sensor($this->monitoringA, 'SNS-A');
    }

    public function test_client_can_create_project_scoped_hydromet_operational_relationship(): void
    {
        $response = $this->actingAs($this->clientUser($this->clientA))
            ->postJson(route('hydromet-ews.relationships.store'), [
                'project_id' => $this->projectA->id,
                'corridor_id' => $this->corridorA->id,
                'monitoring_station_id' => $this->monitoringA->id,
                'warning_station_id' => $this->warningA->id,
                'relationship_code' => 'EWS-A',
                'name' => 'Corridor A EWS',
                'status' => 'active',
            ]);

        $response->assertCreated()
            ->assertJsonPath('relationship.project_id', $this->projectA->id)
            ->assertJsonPath('relationship.corridor_code', 'COR-A')
            ->assertJsonPath('relationship.monitoring_station_code', 'MS-A')
            ->assertJsonPath('relationship.warning_station_code', 'WS-A');

        $this->assertDatabaseHas('hydromet_ews_relationships', [
            'project_id' => $this->projectA->id,
            'corridor_id' => $this->corridorA->id,
            'monitoring_station_id' => $this->monitoringA->id,
            'warning_station_id' => $this->warningA->id,
        ]);
    }

    public function test_invalid_cross_project_operational_relation_is_rejected(): void
    {
        $this->actingAs($this->clientUser($this->clientA))
            ->postJson(route('hydromet-ews.relationships.store'), [
                'project_id' => $this->projectA->id,
                'corridor_id' => $this->corridorA->id,
                'monitoring_station_id' => $this->monitoringB->id,
                'warning_station_id' => $this->warningA->id,
                'relationship_code' => 'EWS-CROSS',
                'name' => 'Cross Project EWS',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('hydromet_ews_relationships', ['relationship_code' => 'EWS-CROSS']);
    }

    public function test_project_isolation_blocks_other_client_hydromet_configuration_access(): void
    {
        $relationshipB = $this->relationship($this->projectB, $this->corridorB, $this->monitoringB, $this->warningB, 'EWS-B');

        $this->actingAs($this->clientUser($this->clientA))
            ->getJson(route('hydromet-ews.configuration.show', $relationshipB))
            ->assertForbidden();
    }

    public function test_client_can_configure_hazard_classification_with_waspada_siaga_awas_state(): void
    {
        $relationship = $this->relationship($this->projectA, $this->corridorA, $this->monitoringA, $this->warningA, 'EWS-HAZ');

        $response = $this->actingAs($this->clientUser($this->clientA))
            ->postJson(route('hydromet-ews.hazard-classifications.store'), [
                'hydromet_ews_relationship_id' => $relationship->id,
                'sensor_id' => $this->sensorA->id,
                'canonical_parameter_id' => $this->parameter->id,
                'classification_code' => 'HZ-WL-A',
                'reading_method' => HydrometHazardClassification::METHOD_ABSOLUTE,
                'threshold_config' => [
                    'unit' => 'm',
                    'source' => 'client_configured',
                ],
                'hazard_levels' => [
                    'WASPADA' => ['threshold' => 2.0],
                    'SIAGA' => ['threshold' => 3.0],
                    'AWAS' => ['threshold' => 4.0],
                ],
                'status' => 'draft',
            ]);

        $response->assertCreated()
            ->assertJsonPath('classification.project_id', $this->projectA->id)
            ->assertJsonPath('classification.reading_method', 'Absolute')
            ->assertJsonPath('classification.hazard_levels.WASPADA.level', 'WASPADA')
            ->assertJsonPath('classification.hazard_levels.SIAGA.level', 'SIAGA')
            ->assertJsonPath('classification.hazard_levels.AWAS.level', 'AWAS')
            ->assertJsonPath('classification.evaluation_engine', 'configuration_only');

        $this->assertDatabaseHas('hydromet_hazard_classifications', [
            'classification_code' => 'HZ-WL-A',
            'project_id' => $this->projectA->id,
            'corridor_id' => $this->corridorA->id,
            'monitoring_station_id' => $this->monitoringA->id,
        ]);
    }

    public function test_hazard_classification_rejects_sensor_from_other_station(): void
    {
        $relationship = $this->relationship($this->projectA, $this->corridorA, $this->monitoringA, $this->warningA, 'EWS-SENSOR');
        $foreignSensor = $this->sensor($this->monitoringB, 'SNS-B');

        $this->actingAs($this->clientUser($this->clientA))
            ->postJson(route('hydromet-ews.hazard-classifications.store'), [
                'hydromet_ews_relationship_id' => $relationship->id,
                'sensor_id' => $foreignSensor->id,
                'classification_code' => 'HZ-CROSS-SENSOR',
                'parameter' => 'WaterLevel',
                'reading_method' => HydrometHazardClassification::METHOD_MOVING_AVERAGE,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('hydromet_hazard_classifications', ['classification_code' => 'HZ-CROSS-SENSOR']);
    }

    public function test_client_can_configure_wdam_warning_station_assignment_and_provider_abstractions(): void
    {
        $relationship = $this->relationship($this->projectA, $this->corridorA, $this->monitoringA, $this->warningA, 'EWS-WDAM');

        $response = $this->actingAs($this->clientUser($this->clientA))
            ->postJson(route('hydromet-ews.wdam-configs.store'), [
                'hydromet_ews_relationship_id' => $relationship->id,
                'warning_station_id' => $this->warningA->id,
                'wdam_code' => 'WDAM-A',
                'dashboard_notification_enabled' => true,
                'registered_recipients' => [
                    ['name' => 'Duty Officer', 'channel' => 'sms', 'recipient_ref' => 'officer-1'],
                ],
                'sms_enabled' => true,
                'sms_provider_ref' => 'existing-sms-provider-ref',
                'whatsapp_enabled' => true,
                'whatsapp_provider_ref' => 'existing-whatsapp-provider-ref',
                'warning_station_assignment_enabled' => true,
                'automatic_activation_enabled' => true,
                'authority_method' => 'authorized_operator_approval',
                'status' => 'draft',
            ]);

        $response->assertCreated()
            ->assertJsonPath('wdam.dashboard_notification', true)
            ->assertJsonPath('wdam.sms.provider_ref', 'existing-sms-provider-ref')
            ->assertJsonPath('wdam.whatsapp.provider_ref', 'existing-whatsapp-provider-ref')
            ->assertJsonPath('wdam.warning_activation.warning_station_code', 'WS-A')
            ->assertJsonPath('wdam.warning_activation.automatic_activation', true)
            ->assertJsonPath('wdam.warning_activation.execution_implemented', false);

        $this->assertDatabaseHas('hydromet_wdam_configs', [
            'wdam_code' => 'WDAM-A',
            'project_id' => $this->projectA->id,
            'warning_station_id' => $this->warningA->id,
            'sms_provider_ref' => 'existing-sms-provider-ref',
            'whatsapp_provider_ref' => 'existing-whatsapp-provider-ref',
        ]);
    }

    public function test_wdam_rejects_cross_project_warning_station_assignment(): void
    {
        $relationship = $this->relationship($this->projectA, $this->corridorA, $this->monitoringA, $this->warningA, 'EWS-WDAM-CROSS');

        $this->actingAs($this->clientUser($this->clientA))
            ->postJson(route('hydromet-ews.wdam-configs.store'), [
                'hydromet_ews_relationship_id' => $relationship->id,
                'warning_station_id' => $this->warningB->id,
                'wdam_code' => 'WDAM-CROSS',
                'warning_station_assignment_enabled' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('hydromet_wdam_configs', ['wdam_code' => 'WDAM-CROSS']);
    }

    public function test_client_can_configure_hydromet_but_cannot_create_registry_assets(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)
            ->post(route('project-monitoring-stations.store'), [
                'workspace_id' => $this->workspaceA->id,
                'station_code' => 'MS-CLIENT',
                'name' => 'Client Monitoring Station',
                'station_type' => 'hydromet_monitoring',
                'logger_status' => 'Active',
                'connectivity_status' => 'Online',
                'status' => 'Normal',
            ])
            ->assertForbidden();

        $this->actingAs($client)
            ->post(route('project-warning-stations.store'), [
                'workspace_id' => $this->workspaceA->id,
                'station_code' => 'WS-CLIENT',
                'name' => 'Client Warning Station',
                'controller_status' => 'Standby',
                'status' => 'Normal',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('monitoring_stations', ['station_code' => 'MS-CLIENT']);
        $this->assertDatabaseMissing('warning_stations', ['station_code' => 'WS-CLIENT']);
    }

    private function clientUser(Client $client): User
    {
        $user = User::create([
            'name' => 'Client User',
            'email' => uniqid('client_', true) . '@test.com',
            'password' => bcrypt('password'),
            'dob' => '2000-01-01',
            'avatar' => 'images/avatar-1.jpg',
            'type' => 'client',
            'client_id' => $client->id,
            'status' => 'active',
        ]);
        $user->assignRole($this->clientRole);

        return $user;
    }

    private function corridor(Project $project, GeospatialWorkspace $workspace, string $code): CorridorMonitoring
    {
        return CorridorMonitoring::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_code' => $code,
            'name' => $code,
            'path_coordinates' => [[-0.92, 100.36], [-0.91, 100.38]],
            'status' => 'Active',
        ]);
    }

    private function monitoringStation(
        Project $project,
        GeospatialWorkspace $workspace,
        string $code,
        CorridorMonitoring $corridor
    ): MonitoringStation {
        return MonitoringStation::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_id' => $corridor->id,
            'station_code' => $code,
            'name' => $code,
            'station_type' => 'hydromet_monitoring',
            'registration_status' => 'registered',
            'logger_status' => 'Active',
            'connectivity_status' => 'Online',
            'status' => 'Normal',
        ]);
    }

    private function warningStation(
        Project $project,
        GeospatialWorkspace $workspace,
        string $code,
        MonitoringStation $monitoringStation
    ): WarningStation {
        return WarningStation::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'monitoring_station_id' => $monitoringStation->id,
            'station_code' => $code,
            'name' => $code,
            'controller_status' => 'Standby',
            'registration_status' => 'registered',
            'status' => 'Normal',
        ]);
    }

    private function relationship(
        Project $project,
        CorridorMonitoring $corridor,
        MonitoringStation $monitoringStation,
        WarningStation $warningStation,
        string $code
    ): HydrometEwsRelationship {
        return HydrometEwsRelationship::create([
            'project_id' => $project->id,
            'corridor_id' => $corridor->id,
            'monitoring_station_id' => $monitoringStation->id,
            'warning_station_id' => $warningStation->id,
            'relationship_code' => $code,
            'name' => $code,
            'status' => 'active',
        ]);
    }

    private function sensor(MonitoringStation $station, string $code): Sensor
    {
        $prefix = MstPrefix::firstOrCreate(
            ['prefix_code' => 'PFX'],
            ['name' => 'Default Prefix', 'status' => 'Active']
        );

        return Sensor::create([
            'workspace_id' => $station->workspace_id,
            'monitoring_station_id' => $station->id,
            'mst_prefix_id' => $prefix->id,
            'slave_id' => '1',
            'address' => (string) Sensor::count(),
            'function_code' => 'FC03',
            'quantity' => 1,
            'poll_interval_ms' => 1000,
            'sensor_code' => $code,
            'type' => 'water_level',
            'parameter' => 'WaterLevel',
            'data_type' => 'float32',
            'scale_factor' => 1,
            'offset' => 0,
            'unit' => 'm',
            'reading_method' => 'Absolute',
            'alert_level' => 'Normal',
            'status' => 'Normal',
        ]);
    }
}
