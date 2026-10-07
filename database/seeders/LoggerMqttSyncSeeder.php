<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoggerMqttSyncSeeder extends Seeder
{
    private const PROJECT_ID = 4;
    private const MONITORING_STATION_ID = 4;
    private const CLOUD_MQTT_BROKER = 'mqtt://139.59.100.220:1883';

    public function run(): void
    {
        DB::transaction(function () {
            $mqtt31 = $this->normalizeMqttConfiguration(
                ['MQTT-LAN-REDNODE-BLIIOT-3.1', 'MQTT-LAN-REDNODE-BLIIOT-011'],
                'MQTT-LAN-REDNODE-BLIIOT-3.1',
                'LAN MQTT RedNode 3.1',
                'resq/telemetry/REDNODE-BLIIOT-3.1/#'
            );

            $mqtt50110 = $this->normalizeMqttConfiguration(
                ['MQTT-LAN-REDNODE-BLIIOT-50.110', 'MQTT-LAN-REDNODE-BLIIOT-BARU-50.110'],
                'MQTT-LAN-REDNODE-BLIIOT-50.110',
                'LAN MQTT RedNode 50.110',
                'resq/telemetry/REDNODE-BLIIOT-50.110/#'
            );

            $this->normalizeDataLogger(
                ['REDNODE-BLIIOT-3.1', 'REDNODE-BLIIOT-011'],
                'REDNODE-BLIIOT-3.1',
                [
                    'serial_number' => '3.1',
                    'device_label' => 'BL118-bliiot',
                    'remote_host' => '192.168.3.1',
                    'node_red_publish_topic' => 'resq/telemetry/REDNODE-BLIIOT-3.1',
                    'node_red_mqtt_configuration_id' => $mqtt31?->id,
                    'logger_status' => 'Active',
                ]
            );

            $logger50110 = $this->normalizeDataLogger(
                ['REDNODE-BLIIOT-50.110'],
                'REDNODE-BLIIOT-50.110',
                [
                    'serial_number' => '50.110',
                    'device_label' => 'BL371-bliiot',
                    'remote_host' => '192.168.50.110',
                    'node_red_publish_topic' => 'resq/telemetry/REDNODE-BLIIOT-50.110',
                    'node_red_mqtt_configuration_id' => $mqtt50110?->id,
                    'logger_status' => 'Active',
                ]
            );

            $this->assignActiveDemoSensorsToLogger($logger50110?->id);
            $this->deactivateStaleRows();
        });
    }

    private function normalizeMqttConfiguration(array $candidateCodes, string $targetCode, string $name, string $consumerTopic): ?object
    {
        if (! Schema::hasTable('mqtt_configurations')) {
            return null;
        }

        $target = DB::table('mqtt_configurations')->where('configuration_code', $targetCode)->first();
        $legacy = DB::table('mqtt_configurations')
            ->whereIn('configuration_code', array_values(array_diff($candidateCodes, [$targetCode])))
            ->orderBy('id')
            ->first();

        if ($target && $legacy && (int) $target->id !== (int) $legacy->id) {
            $this->repointMqttConfiguration($legacy->id, $target->id);
            DB::table('mqtt_configurations')->where('id', $legacy->id)->delete();
        }

        $row = $target ?: $legacy;
        $payload = [
            'project_id' => self::PROJECT_ID,
            'configuration_code' => $targetCode,
            'name' => $name,
            'broker_url' => self::CLOUD_MQTT_BROKER,
            'username' => null,
            'password_ciphertext' => null,
            'consumer_enabled' => 1,
            'consumer_topic' => $consumerTopic,
            'consumer_qos' => 0,
            'example_payload' => null,
            'sensor_code_path' => 'sensor_code',
            'producer_enabled' => 0,
            'producer_topic' => null,
            'producer_qos' => 0,
            'producer_retain' => 0,
            'publish_canonical' => 0,
            'publish_warning' => 0,
            'canonical_parameter_ids' => null,
            'warning_levels' => null,
            'canonical_template' => null,
            'warning_template' => null,
            'is_active' => 1,
            'connection_status' => 'inactive',
            'last_error' => null,
            'runtime_metrics' => json_encode([]),
            'updated_at' => now(),
        ];

        if ($row) {
            DB::table('mqtt_configurations')->where('id', $row->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('mqtt_configurations')->insert($payload);
        }

        return DB::table('mqtt_configurations')->where('configuration_code', $targetCode)->first();
    }

    private function normalizeDataLogger(array $candidateCodes, string $targetCode, array $attributes): ?object
    {
        if (! Schema::hasTable('data_loggers')) {
            return null;
        }

        $target = DB::table('data_loggers')->where('logger_code', $targetCode)->first();
        $legacy = DB::table('data_loggers')
            ->whereIn('logger_code', array_values(array_diff($candidateCodes, [$targetCode])))
            ->orderBy('id')
            ->first();

        if ($target && $legacy && (int) $target->id !== (int) $legacy->id) {
            $this->repointDataLogger($legacy->id, $target->id);
            DB::table('data_loggers')->where('id', $legacy->id)->delete();
        }

        $row = $target ?: $legacy;
        $payload = array_merge([
            'monitoring_station_id' => self::MONITORING_STATION_ID,
            'logger_code' => $targetCode,
            'logger_model' => 'RedNode Bliiot',
            'vendor' => 'Bliiot',
            'firmware_version' => 'Ubuntu 20.04.6 LTS',
            'remote_ssh_port' => 22,
            'remote_ssh_user' => 'root',
            'remote_ssh_password' => null,
            'remote_gateway_path' => '/root/rednode-gateway',
            'remote_last_status' => null,
            'remote_last_message' => null,
            'node_red_service_name' => 'rednode-gateway',
            'node_red_user_dir' => '/root/rednode-gateway',
            'node_red_environment_file' => '/etc/systemd/system/rednode-gateway.service',
            'node_red_restart_command' => 'systemctl restart rednode-gateway',
            'node_red_last_status' => null,
            'node_red_last_message' => null,
            'poll_interval_ms' => 2000,
            'updated_at' => now(),
        ], $attributes);

        if ($row) {
            DB::table('data_loggers')->where('id', $row->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('data_loggers')->insert($payload);
        }

        return DB::table('data_loggers')->where('logger_code', $targetCode)->first();
    }

    private function repointMqttConfiguration(int $fromId, int $toId): void
    {
        foreach ([
            ['data_loggers', 'node_red_mqtt_configuration_id'],
            ['sensors', 'mqtt_configuration_id'],
            ['mqtt_outbox_messages', 'mqtt_configuration_id'],
        ] as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, $fromId)->update([$column => $toId]);
            }
        }
    }

    private function repointDataLogger(int $fromId, int $toId): void
    {
        foreach ([
            ['sensors', 'data_logger_id'],
            ['connectivity_configs', 'data_logger_id'],
            ['device_credentials', 'data_logger_id'],
            ['raw_data_ingestions', 'data_logger_id'],
            ['telemetry_readings', 'data_logger_id'],
            ['canonical_observations', 'data_logger_id'],
            ['data_logger_discoveries', 'matched_data_logger_id'],
        ] as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, $fromId)->update([$column => $toId]);
            }
        }
    }

    private function assignActiveDemoSensorsToLogger(?int $loggerId): void
    {
        if (! $loggerId || ! Schema::hasTable('sensors') || ! Schema::hasColumn('sensors', 'data_logger_id')) {
            return;
        }

        $payload = [
            'data_logger_id' => $loggerId,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('sensors', 'input_source')) {
            $payload['input_source'] = 'data_logger';
        }

        if (Schema::hasColumn('sensors', 'mqtt_configuration_id')) {
            $payload['mqtt_configuration_id'] = null;
        }

        DB::table('sensors')
            ->whereIn('sensor_code', ['RIKA-CUACA', 'SENSOR-LEMBAB'])
            ->update($payload);
    }

    private function deactivateStaleRows(): void
    {
        if (Schema::hasTable('mqtt_configurations')) {
            DB::table('mqtt_configurations')
                ->whereIn('configuration_code', ['MQTT-LAN-REDNODE-BLIIOT-BARU'])
                ->update([
                    'is_active' => 0,
                    'connection_status' => 'inactive',
                    'last_error' => null,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('data_loggers')) {
            DB::table('data_loggers')
                ->whereIn('logger_code', ['REDNODE-BLIIOT-BARU'])
                ->update([
                    'logger_status' => 'Inactive',
                    'node_red_publish_topic' => 'resq/telemetry/REDNODE-BLIIOT-BARU',
                    'updated_at' => now(),
                ]);
        }
    }
}
