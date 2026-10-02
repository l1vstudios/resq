<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sinkronisasi data konfigurasi Logger & MQTT dari lingkungan lokal.
 *
 * Dipakai agar DB cloud memiliki baris data_loggers dan mqtt_configurations
 * yang sama dengan lokal (host RedNode sudah diseragamkan ke 192.168.3.110).
 *
 * Memakai updateOrInsert berdasarkan 'id' sehingga idempotent: baris yang
 * sudah ada di cloud akan ditimpa, baris baru akan ditambahkan. Tabel
 * telemetry TIDAK disentuh.
 *
 * CATATAN KREDENSIAL: kolom remote_ssh_password & password_ciphertext berisi
 * nilai terenkripsi dengan APP_KEY lingkungan asal. Nilai ini hanya dapat
 * didekripsi di cloud bila APP_KEY cloud SAMA dengan asal. Bila berbeda,
 * set ulang password lewat UI setelah seeding.
 */
class LoggerMqttSyncSeeder extends Seeder
{
    public function run(): void
    {
        $dataLoggers = [
            [
                'id' => 5,
                'monitoring_station_id' => 4,
                'logger_code' => 'REDNODE-BLIIOT-011',
                'serial_number' => 'f7e9724768f1dee5',
                'logger_model' => 'RedNode Bliiot',
                'vendor' => 'Bliiot',
                'firmware_version' => 'Ubuntu 20.04.6 LTS',
                'device_label' => 'BL118-bliiot',
                'remote_host' => '192.168.3.110',
                'remote_ssh_port' => 22,
                'remote_ssh_user' => 'root',
                'remote_ssh_password' => 'eyJpdiI6ImNTcmtKaDZpTDk1UUtZRm5FVExqY0E9PSIsInZhbHVlIjoiTXFNRkhHV2x4L0xpbGpSSmZiN0Mzdz09IiwibWFjIjoiNTgyNWQzY2U1ODVjMjkwNDVhZWUyZjhmZTg4ZWQ5MmE2ZTM0MGQ5YmY3YTIxY2MxYTkyZGE2NTk2NmZhZDMxNSIsInRhZyI6IiJ9',
                'remote_gateway_path' => '/root/rednode-gateway',
                'remote_last_tested_at' => '2026-10-02 05:01:08',
                'remote_last_status' => 'Success',
                'remote_last_message' => 'Gateway heartbeat/config dari 192.168.3.110',
                'node_red_mqtt_configuration_id' => 3,
                'node_red_publish_topic' => 'resq/telemetry/#',
                'node_red_service_name' => 'rednode-gateway',
                'node_red_user_dir' => '/root/rednode-gateway',
                'node_red_environment_file' => '/etc/systemd/system/rednode-gateway.service.d/resq-mqtt.conf',
                'node_red_restart_command' => 'systemctl restart rednode-gateway',
                'node_red_last_applied_at' => null,
                'node_red_last_tested_at' => '2026-09-29 06:55:09',
                'node_red_last_status' => 'Success',
                'node_red_last_message' => 'Simulasi MQTT dari remote server berhasil.',
                'logger_status' => 'Inactive',
                'poll_interval_ms' => 2000,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-10-02 05:11:14',
            ],
            [
                'id' => 8,
                'monitoring_station_id' => 4,
                'logger_code' => 'REDNODE-BLIIOT-BARU',
                'serial_number' => 'XXXX',
                'logger_model' => 'RedNode Bliiot',
                'vendor' => 'Bliiot',
                'firmware_version' => 'Ubuntu 20.04.5 LTS',
                'device_label' => 'BL118-bliiot',
                'remote_host' => '192.168.3.110',
                'remote_ssh_port' => 22,
                'remote_ssh_user' => 'root',
                'remote_ssh_password' => null,
                'remote_gateway_path' => '/root/rednode-gateway',
                'remote_last_tested_at' => '2026-10-01 10:32:11',
                'remote_last_status' => 'Success',
                'remote_last_message' => 'Logger bisa dijangkau dari server.',
                'node_red_mqtt_configuration_id' => 4,
                'node_red_publish_topic' => 'resq/telemetry/#',
                'node_red_service_name' => 'rednode-gateway',
                'node_red_user_dir' => '/root/rednode-gateway',
                'node_red_environment_file' => '/etc/systemd/system/rednode-gateway.service.d/resq-mqtt.conf',
                'node_red_restart_command' => 'systemctl restart rednode-gateway',
                'node_red_last_applied_at' => null,
                'node_red_last_tested_at' => '2026-10-01 10:32:16',
                'node_red_last_status' => 'Failed',
                'node_red_last_message' => 'Isi IP / Host Remote, SSH User, dan SSH Password di Data Loggers dulu.',
                'logger_status' => 'Active',
                'poll_interval_ms' => 2000,
                'created_at' => '2026-09-29 08:06:55',
                'updated_at' => '2026-10-02 05:11:14',
            ],
        ];

        $mqttConfigurations = [
            [
                'id' => 3,
                'project_id' => 4,
                'configuration_code' => 'MQTT-LAN-REDNODE-BLIIOT-011',
                'name' => 'LAN MQTT RedNode',
                'broker_url' => 'mqtt://192.168.3.110:1883',
                'username' => 'root',
                'password_ciphertext' => 'v1:O2ItURQuses6fGnh:RugdSBhDBCtmCgaLFYvr2Q==:Z2drGQ+GMlPNJg==',
                'consumer_enabled' => 1,
                'consumer_topic' => 'resq/telemetry/#',
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
                'is_active' => 0,
                'connection_status' => 'inactive',
                'last_connected_at' => '2026-09-29 16:12:16',
                'last_received_at' => '2026-09-29 15:34:01',
                'last_published_at' => null,
                'last_error' => null,
                'runtime_metrics' => '[]',
                'created_at' => '2026-08-27 05:26:15',
                'updated_at' => '2026-10-02 05:11:14',
            ],
            [
                'id' => 4,
                'project_id' => 4,
                'configuration_code' => 'MQTT-LAN-REDNODE-BLIIOT-BARU',
                'name' => 'LAN MQTT RedNode BARU',
                'broker_url' => 'mqtt://192.168.3.110:1883',
                'username' => 'root',
                'password_ciphertext' => null,
                'consumer_enabled' => 1,
                'consumer_topic' => 'resq/telemetry/#',
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
                'connection_status' => 'connected',
                'last_connected_at' => '2026-10-02 12:11:19',
                'last_received_at' => null,
                'last_published_at' => null,
                'last_error' => null,
                'runtime_metrics' => '[]',
                'created_at' => '2026-09-29 08:12:30',
                'updated_at' => '2026-10-02 12:11:19',
            ],
        ];

        if (Schema::hasTable('mqtt_configurations')) {
            foreach ($mqttConfigurations as $row) {
                DB::table('mqtt_configurations')->updateOrInsert(['id' => $row['id']], $row);
            }
        }

        if (Schema::hasTable('data_loggers')) {
            foreach ($dataLoggers as $row) {
                DB::table('data_loggers')->updateOrInsert(['id' => $row['id']], $row);
            }
        }
    }
}
