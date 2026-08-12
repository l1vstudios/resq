<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarningStationDevicesSeeder extends Seeder
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
                'device_code' => 'WSCP-DEMO-01',
                'device_type' => 'wscp',
                'name' => 'Demo WSCP',
                'vendor' => 'RESQ',
                'model' => 'Demo Output',
                'serial_number' => 'WSCP-DEMO-01-SN',
                'expected' => 1,
                'availability_state' => 'available',
                'health_state' => 'ok',
                'last_heartbeat_at' => '2026-08-11 07:58:00',
                'health_payload' => '{"link": "ok", "battery": "normal"}',
                'status' => 'registered',
                'notes' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ],
            [
                'id' => 2,
                'project_id' => 4,
                'warning_station_id' => 4,
                'device_code' => 'ASCP-DEMO-01',
                'device_type' => 'ascp',
                'name' => 'Demo ASCP',
                'vendor' => 'RESQ',
                'model' => 'Demo Output',
                'serial_number' => 'ASCP-DEMO-01-SN',
                'expected' => 1,
                'availability_state' => 'available',
                'health_state' => 'ok',
                'last_heartbeat_at' => '2026-08-11 07:58:00',
                'health_payload' => '{"link": "ok", "battery": "normal"}',
                'status' => 'registered',
                'notes' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ],
            [
                'id' => 3,
                'project_id' => 4,
                'warning_station_id' => 4,
                'device_code' => 'SIREN-DEMO-01',
                'device_type' => 'siren',
                'name' => 'Demo Siren',
                'vendor' => 'RESQ',
                'model' => 'Demo Output',
                'serial_number' => 'SIREN-DEMO-01-SN',
                'expected' => 1,
                'availability_state' => 'available',
                'health_state' => 'ok',
                'last_heartbeat_at' => '2026-08-11 07:58:00',
                'health_payload' => '{"link": "ok", "battery": "normal"}',
                'status' => 'registered',
                'notes' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ],
            [
                'id' => 4,
                'project_id' => 4,
                'warning_station_id' => 4,
                'device_code' => 'BEACON-DEMO-01',
                'device_type' => 'beacon',
                'name' => 'Demo Beacon',
                'vendor' => 'RESQ',
                'model' => 'Demo Output',
                'serial_number' => 'BEACON-DEMO-01-SN',
                'expected' => 1,
                'availability_state' => 'available',
                'health_state' => 'ok',
                'last_heartbeat_at' => '2026-08-11 07:58:00',
                'health_payload' => '{"link": "ok", "battery": "normal"}',
                'status' => 'registered',
                'notes' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('warning_station_devices')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}