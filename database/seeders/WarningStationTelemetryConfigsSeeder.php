<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarningStationTelemetryConfigsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => 1,
                'project_id' => 4,
                'warning_station_id' => 4,
                'config_code' => 'WSTC-DEMO-01',
                'broker_config_ref' => 'shared-demo-mqtt',
                'protocol' => 'MQTT',
                'host_or_endpoint' => 'mqtt://demo-broker.local',
                'port' => 1883,
                'topic' => 'sentinel/demo/ws-demo-01/heartbeat',
                'qos' => 1,
                'retain' => 0,
                'credential_ref' => 'secret:demo-warning-mqtt',
                'connection_status' => 'connected',
                'last_connected_at' => '2026-08-11 07:52:00',
                'last_seen_at' => '2026-08-11 07:58:00',
                'last_error' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        foreach ($data as $row) {
            DB::table('warning_station_telemetry_configs')->insertOrIgnore($row);
        }
    }
}