<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarningStationDeviceHeartbeatsSeeder extends Seeder
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
                'warning_station_device_id' => 1,
                'device_code' => 'WSCP-DEMO-01',
                'device_type' => 'wscp',
                'availability_state' => 'available',
                'health_state' => 'ok',
                'observed_at' => '2026-08-11 07:57:00',
                'received_at' => '2026-08-11 07:58:00',
                'health_payload' => '{"source": "demo-seeder"}',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 2,
                'project_id' => 4,
                'warning_station_id' => 4,
                'warning_station_device_id' => 2,
                'device_code' => 'ASCP-DEMO-01',
                'device_type' => 'ascp',
                'availability_state' => 'available',
                'health_state' => 'ok',
                'observed_at' => '2026-08-11 07:57:00',
                'received_at' => '2026-08-11 07:58:00',
                'health_payload' => '{"source": "demo-seeder"}',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 3,
                'project_id' => 4,
                'warning_station_id' => 4,
                'warning_station_device_id' => 3,
                'device_code' => 'SIREN-DEMO-01',
                'device_type' => 'siren',
                'availability_state' => 'available',
                'health_state' => 'ok',
                'observed_at' => '2026-08-11 07:57:00',
                'received_at' => '2026-08-11 07:58:00',
                'health_payload' => '{"source": "demo-seeder"}',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 4,
                'project_id' => 4,
                'warning_station_id' => 4,
                'warning_station_device_id' => 4,
                'device_code' => 'BEACON-DEMO-01',
                'device_type' => 'beacon',
                'availability_state' => 'available',
                'health_state' => 'ok',
                'observed_at' => '2026-08-11 07:57:00',
                'received_at' => '2026-08-11 07:58:00',
                'health_payload' => '{"source": "demo-seeder"}',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('warning_station_device_heartbeats')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}