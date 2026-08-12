<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CorridorMonitoring;
use App\Models\GeospatialWorkspace;
use App\Models\HydrometEwsRelationship;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\ProjectRecoveryAccount;
use App\Models\Role;
use App\Models\Sensor;
use App\Models\SentinelNotification;
use App\Models\TelemetryReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalPhaseReportingInboxSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Role $clientRole;

    private Client $clientA;

    private Client $clientB;

    private Project $projectA;

    private Project $projectB;

    private CorridorMonitoring $corridorA;

    private CorridorMonitoring $corridorB;

    private MonitoringStation $stationA;

    private MonitoringStation $stationB;

    private Sensor $sensorA;

    private HydrometEwsRelationship $ewsB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRole = Role::create(['name' => 'ClientAdmin', 'type' => 'system']);
        Role::create(['name' => 'ClientOperator', 'type' => 'system']);
        Role::create(['name' => 'ClientViewer', 'type' => 'system']);

        $this->clientA = Client::create(['client_code' => 'CLIENT-A', 'name' => 'Client A']);
        $this->clientB = Client::create(['client_code' => 'CLIENT-B', 'name' => 'Client B']);

        [$this->projectA, $this->corridorA, $this->stationA, $this->sensorA] = $this->stack($this->clientA, 'A');
        [$this->projectB, $this->corridorB, $this->stationB] = $this->stack($this->clientB, 'B');

        TelemetryReading::create([
            'sensor_id' => $this->sensorA->id,
            'value' => '2.40',
            'numeric_value' => '2.40',
            'alert_level' => 'Waspada',
            'status' => 'Waspada',
            'received_at' => now()->subHour(),
        ]);

        $this->ewsB = HydrometEwsRelationship::create([
            'project_id' => $this->projectB->id,
            'corridor_id' => $this->corridorB->id,
            'monitoring_station_id' => $this->stationB->id,
            'relationship_code' => 'EWS-B',
            'name' => 'EWS B',
            'status' => 'active',
        ]);
    }

    public function test_reporting_generates_printable_csv_and_excel_compatible_outputs_without_mutation(): void
    {
        $client = $this->clientUser($this->clientA);
        $base = [
            'target_type' => 'station',
            'target_id' => $this->stationA->id,
            'parameter' => 'WaterLevel',
            'from' => now()->subDay()->format('Y-m-d H:i:s'),
            'to' => now()->format('Y-m-d H:i:s'),
        ];

        $this->actingAs($client)->get(route('client-operations.reporting.index', [
            'target_type' => 'station',
            'target_id' => $this->stationA->id,
            'project_id' => $this->projectA->id,
        ]))->assertOk()
            ->assertSee('Generate Report')
            ->assertSee('Monitoring Station');

        $this->actingAs($client)->get(route('client-operations.reporting.generate', $base + ['output' => 'print']))
            ->assertOk()
            ->assertSee('Printable Report')
            ->assertSee('WaterLevel')
            ->assertSee('2.400000');

        $this->actingAs($client)->get(route('client-operations.reporting.generate', $base + ['output' => 'csv']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($client)->get(route('client-operations.reporting.generate', $base + ['output' => 'excel']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');

        $this->assertSame(1, TelemetryReading::where('sensor_id', $this->sensorA->id)->count());
    }

    public function test_inbox_tracks_unread_detail_history_and_context_scope(): void
    {
        $client = $this->clientUser($this->clientA);
        $notification = SentinelNotification::create([
            'client_id' => $this->clientA->id,
            'project_id' => $this->projectA->id,
            'corridor_id' => $this->corridorA->id,
            'monitoring_station_id' => $this->stationA->id,
            'category' => 'Operational',
            'event_type' => 'Waspada',
            'title' => 'Waspada threshold reached',
            'body' => 'Station reached Waspada.',
            'source_context' => ['parameter' => 'WaterLevel'],
            'occurred_at' => now(),
        ]);
        $other = SentinelNotification::create([
            'client_id' => $this->clientB->id,
            'project_id' => $this->projectB->id,
            'category' => 'System',
            'event_type' => 'System / Information Notification',
            'title' => 'Other client notice',
            'occurred_at' => now(),
        ]);

        $this->actingAs($client)->get(route('client-operations.inbox.index'))
            ->assertOk()
            ->assertSee('Notification History')
            ->assertSee('Waspada threshold reached')
            ->assertDontSee('Other client notice');

        $this->actingAs($client)->get(route('client-operations.inbox.show', $notification))
            ->assertOk()
            ->assertSee('Open Context');

        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($client)->get(route('client-operations.inbox.show', $other))->assertForbidden();
    }

    public function test_client_user_management_enforces_scope_and_five_operational_user_limit(): void
    {
        $admin = $this->clientUser($this->clientA);
        ProjectRecoveryAccount::create([
            'project_id' => $this->projectA->id,
            'recovery_username' => 'recovery-a',
            'recovery_password_hash' => bcrypt('recovery'),
            'status' => 'unused',
        ]);

        for ($i = 1; $i <= 4; $i++) {
            $this->actingAs($admin)->post(route('client-operations.users.store'), [
                'name' => 'Client A User '.$i,
                'email' => 'client-a-'.$i.'@test.com',
                'password' => 'password123',
                'status' => 'active',
                'role' => 'ClientOperator',
                'project_ids' => [$this->projectA->id],
                'access_level' => 'operator',
            ])->assertRedirect();
        }

        $this->assertSame(5, User::where('type', 'client')->where('client_id', $this->clientA->id)->count());

        $this->actingAs($admin)->post(route('client-operations.users.store'), [
            'name' => 'Too Many',
            'email' => 'too-many@test.com',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'ClientViewer',
            'project_ids' => [$this->projectA->id],
            'access_level' => 'viewer',
        ])->assertStatus(422);

        $otherClientUser = $this->clientUser($this->clientB);
        $this->actingAs($admin)->put(route('client-operations.users.update', $otherClientUser), [
            'name' => 'Wrong Scope',
            'email' => $otherClientUser->email,
            'status' => 'suspended',
            'role' => 'ClientViewer',
            'access_level' => 'viewer',
        ])->assertForbidden();
    }

    public function test_security_hardening_blocks_cross_client_direct_access_and_asset_mutations(): void
    {
        $client = $this->clientUser($this->clientA);

        $this->actingAs($client)->get(route('client-operations.projects.show', $this->projectB))->assertForbidden();
        $this->actingAs($client)->get(route('client-operations.corridors.show', [$this->projectB, $this->corridorB]))->assertForbidden();
        $this->actingAs($client)->get(route('client-operations.stations.show', $this->stationB))->assertForbidden();
        $this->actingAs($client)->get(route('hydromet-ews.configuration.show', $this->ewsB))->assertForbidden();
        $this->actingAs($client)->get(route('client-operations.reporting.generate', [
            'target_type' => 'station',
            'target_id' => $this->stationB->id,
            'from' => now()->subDay()->format('Y-m-d H:i:s'),
            'to' => now()->format('Y-m-d H:i:s'),
            'output' => 'print',
        ]))->assertForbidden();

        $this->actingAs($client)->post(route('project-warning-stations.store'), [
            'project_id' => $this->projectA->id,
            'workspace_id' => $this->stationA->workspace_id,
            'station_code' => 'CLIENT-WS',
            'name' => 'Client Warning Station',
            'controller_status' => 'Standby',
            'status' => 'Normal',
        ])->assertForbidden();
    }

    private function clientUser(Client $client): User
    {
        $user = User::create([
            'name' => 'Client User '.$client->client_code,
            'email' => uniqid('client_final_', true).'@test.com',
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

    private function stack(Client $client, string $suffix): array
    {
        $project = Project::create([
            'project_code' => 'PRJ-'.$suffix,
            'name' => 'Project '.$suffix,
            'client_id' => $client->id,
            'status' => 'Active',
        ]);
        $workspace = GeospatialWorkspace::create([
            'project_id' => $project->id,
            'workspace_code' => 'GWS-'.$suffix,
            'name' => 'Workspace '.$suffix,
            'province' => 'Province '.$suffix,
            'city' => 'City '.$suffix,
            'latitude' => -0.90,
            'longitude' => 100.30,
            'status' => 'Normal',
        ]);
        $corridor = CorridorMonitoring::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_code' => 'COR-'.$suffix,
            'name' => 'Corridor '.$suffix,
            'status' => 'Active',
        ]);
        $station = MonitoringStation::create([
            'project_id' => $project->id,
            'workspace_id' => $workspace->id,
            'corridor_id' => $corridor->id,
            'station_code' => 'MS-'.$suffix,
            'name' => 'Monitoring '.$suffix,
            'station_type' => 'hydromet_monitoring',
            'latitude' => -0.91,
            'longitude' => 100.31,
            'registration_status' => 'registered',
            'status' => 'Normal',
        ]);
        $sensor = Sensor::create([
            'workspace_id' => $workspace->id,
            'monitoring_station_id' => $station->id,
            'sensor_code' => 'SNS-'.$suffix,
            'type' => 'water_level',
            'parameter' => 'WaterLevel',
            'value' => '2.4',
            'data_type' => 'float32',
            'scale_factor' => 1,
            'offset' => 0,
            'unit' => 'm',
            'reading_method' => 'Absolute',
            'status' => 'Active',
            'last_seen_at' => now(),
        ]);

        return [$project, $corridor, $station, $sensor];
    }
}
