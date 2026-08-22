<?php

namespace Tests\Feature;

use App\Models\CanonicalParameter;
use App\Models\GeospatialWorkspace;
use App\Models\MonitoringStation;
use App\Models\MqttConfiguration;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\SensorMappingProfile;
use App\Models\User;
use App\Services\MqttCredentialCipher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MqttConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.mqtt.credential_key', base64_encode(str_repeat('k', 32)));
    }

    public function test_sentinel_can_store_encrypted_project_mqtt_configuration(): void
    {
        $user = User::create([
            'name' => 'Operator', 'email' => 'mqtt@example.test', 'password' => bcrypt('secret'),
            'dob' => '2000-01-01', 'avatar' => 'avatar.png', 'type' => 'sentinel', 'status' => 'active',
        ]);
        $project = Project::create(['project_code' => 'PRJ-MQTT', 'name' => 'MQTT Project']);

        $response = $this->actingAs($user)->post(route('mqtt-configurations.store'), [
            'project_id' => $project->id,
            'configuration_code' => 'MQTT-01',
            'name' => 'Primary Broker',
            'broker_url' => 'mqtts://broker.example.test:8883',
            'username' => 'device',
            'password' => 'broker-secret',
            'consumer_enabled' => '1',
            'consumer_topic' => 'project/input/#',
            'consumer_qos' => 1,
            'example_payload' => '{"sensor_code":"SNS-01","data":{"temperature":28.5}}',
            'sensor_code_path' => 'sensor_code',
            'producer_enabled' => '0',
            'producer_qos' => 0,
            'producer_retain' => '0',
            'publish_canonical' => '0',
            'publish_warning' => '0',
            'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $config = MqttConfiguration::firstOrFail();
        $this->assertNotSame('broker-secret', $config->password_ciphertext);
        $this->assertSame('broker-secret', app(MqttCredentialCipher::class)->decrypt($config->password_ciphertext));
        $this->assertSame(28.5, data_get($config->example_payload, 'data.temperature'));
    }

    public function test_invalid_configuration_submit_shows_errors_and_preserves_input(): void
    {
        $user = User::create([
            'name' => 'Operator', 'email' => 'mqtt-validation@example.test', 'password' => bcrypt('secret'),
            'dob' => '2000-01-01', 'avatar' => 'avatar.png', 'type' => 'sentinel', 'status' => 'active',
        ]);
        $project = Project::create(['project_code' => 'PRJ-MQTT-VALIDATION', 'name' => 'MQTT Validation']);

        $this->actingAs($user)->get(route('mqtt-configurations.index'))->assertOk();

        $response = $this->actingAs($user)->followingRedirects()->post(route('mqtt-configurations.store'), [
            'project_id' => $project->id,
            'configuration_code' => 'MQTT-INVALID-01',
            'name' => 'Invalid Broker',
            'broker_url' => 'mqtt://broker.example.test:1883',
            'consumer_enabled' => '1',
            'consumer_topic' => '',
            'consumer_qos' => 1,
            'example_payload' => '',
            'sensor_code_path' => 'sensor_code',
            'producer_enabled' => '0',
            'producer_qos' => 0,
            'producer_retain' => '0',
            'publish_canonical' => '0',
            'publish_warning' => '0',
            'is_active' => '1',
        ]);

        $response->assertOk()
            ->assertSee('Periksa kembali konfigurasi MQTT berikut')
            ->assertSee('MQTT-INVALID-01');
        $this->assertDatabaseCount('mqtt_configurations', 0);
    }

    public function test_mqtt_edit_form_is_available_in_collapsible_sensor_tab(): void
    {
        $user = User::create([
            'name' => 'Operator', 'email' => 'mqtt-edit@example.test', 'password' => bcrypt('secret'),
            'dob' => '2000-01-01', 'avatar' => 'avatar.png', 'type' => 'sentinel', 'status' => 'active',
        ]);
        $project = Project::create(['project_code' => 'PRJ-MQTT-EDIT', 'name' => 'MQTT Edit']);
        MqttConfiguration::create([
            'project_id' => $project->id, 'configuration_code' => 'MQTT-EDIT-01', 'name' => 'Broker',
            'broker_url' => 'mqtt://broker.example.test:1883', 'consumer_enabled' => true,
            'consumer_topic' => 'project/input/#', 'consumer_qos' => 0, 'example_payload' => ['sensor_code' => 'SNS-01'],
            'producer_enabled' => false, 'producer_qos' => 0, 'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('mqtt-configurations.index'))
            ->assertOk()
            ->assertSee("var checkboxInputs = Array.from(inputs).filter", false)
            ->assertSee("input.value = '0';", false);

        $this->actingAs($user)->get(route('projects.index'))
            ->assertOk()
            ->assertSee('data-bs-target="#data-logger-configuration-collapse"', false)
            ->assertSee('data-bs-target="#mqtt-configuration-collapse"', false)
            ->assertSee('data-bs-target="#sensor-configuration-collapse"', false)
            ->assertSee('id="project-mqtt-configuration-form"', false)
            ->assertSee('data-edit-form="#project-mqtt-configuration-form"', false)
            ->assertSee('bootstrap.Collapse.getOrCreateInstance', false)
            ->assertSee('MQTT-EDIT-01');
    }

    public function test_consumer_ingestion_stores_canonical_data_and_producer_outbox(): void
    {
        $project = Project::create(['project_code' => 'PRJ-INGEST', 'name' => 'Ingestion Project']);
        $workspace = GeospatialWorkspace::create([
            'project_id' => $project->id, 'workspace_code' => 'WS-MQTT', 'name' => 'Workspace',
            'province' => 'DKI Jakarta', 'status' => 'Normal',
        ]);
        $station = MonitoringStation::create([
            'workspace_id' => $workspace->id, 'project_id' => $project->id,
            'station_code' => 'MS-MQTT', 'name' => 'Station', 'status' => 'Normal',
        ]);
        $parameter = CanonicalParameter::create([
            'field_identity' => 'temperature', 'domain' => 'meteorology', 'canonical_unit' => '°C',
            'data_type' => 'decimal', 'status' => 'active',
        ]);
        $config = MqttConfiguration::create([
            'project_id' => $project->id, 'configuration_code' => 'MQTT-INGEST', 'name' => 'Broker',
            'broker_url' => 'mqtt://broker:1883', 'consumer_enabled' => true,
            'consumer_topic' => 'project/input/#', 'consumer_qos' => 1, 'example_payload' => ['sensor_code' => 'SNS-01', 'data' => ['temperature' => 28.5]],
            'sensor_code_path' => 'sensor_code', 'producer_enabled' => true, 'producer_topic' => 'project/output',
            'producer_qos' => 1, 'publish_canonical' => true, 'publish_warning' => true,
            'warning_levels' => ['Awas'], 'is_active' => true,
        ]);
        $sensor = Sensor::create([
            'workspace_id' => $workspace->id, 'monitoring_station_id' => $station->id,
            'input_source' => 'mqtt', 'mqtt_configuration_id' => $config->id,
            'sensor_code' => 'SNS-01', 'type' => 'temperature', 'parameter' => 'Temperature',
            'threshold' => '25', 'data_type' => 'float32', 'scale_factor' => 1, 'offset' => 0,
            'unit' => '°C', 'alert_level' => 'Normal', 'status' => 'Normal',
        ]);
        SensorMappingProfile::create([
            'sensor_id' => $sensor->id, 'profile_code' => 'MAP-MQTT-01',
            'communication_path' => 'mqtt', 'source_parameter' => 'data.temperature',
            'value_type' => 'float32', 'scale_factor' => 1, 'offset' => 0,
            'canonical_parameter_id' => $parameter->id, 'value_origin' => 'direct_measurement', 'status' => 'active',
        ]);

        $response = $this->postJson(route('api.mqtt.ingest'), [
            'mqtt_configuration_id' => $config->id,
            'sensor_code' => 'SNS-01',
            'topic' => 'project/input/SNS-01',
            'payload' => ['sensor_code' => 'SNS-01', 'data' => ['temperature' => 28.5]],
            'values' => [['source_path' => 'data.temperature', 'value' => 28.5]],
            'observed_at' => '2026-08-20T12:00:00+07:00',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('canonical_parameter_values', ['canonical_parameter_id' => $parameter->id, 'numeric_value' => 28.5]);
        $this->assertDatabaseHas('telemetry_readings', ['sensor_id' => $sensor->id, 'alert_level' => 'Awas']);
        $this->assertDatabaseCount('mqtt_outbox_messages', 2);
        $this->assertDatabaseHas('mqtt_outbox_messages', ['event_type' => 'canonical', 'status' => 'pending']);
        $this->assertDatabaseHas('mqtt_outbox_messages', ['event_type' => 'warning', 'status' => 'pending']);
    }
}
