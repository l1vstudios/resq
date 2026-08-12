<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarningStationsSeeder extends Seeder
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
                'id' => 4,
                'workspace_id' => 4,
                'project_id' => 4,
                'monitoring_station_id' => 4,
                'station_code' => 'WS-DEMO-01',
                'name' => 'Demo Warning Station 01',
                'zone_id' => 'ZONE-DEMO-01',
                'administrative_location' => 'Lumajang Downstream Demo Zone',
                'coordinate' => '-8.2052,112.9940',
                'latitude' => '-8.2052000',
                'longitude' => '112.9940000',
                'controller_id' => 'WSCP-DEMO-01',
                'controller_model' => 'Demo WSCP',
                'controller_vendor' => 'RESQ',
                'controller_status' => 'Standby',
                'registration_status' => 'registered',
                'registered_at' => '2026-07-27 08:00:00',
                'registered_by_user_id' => 1,
                'output_devices' => '["WSCP", "ASCP", "Siren", "Beacon"]',
                'status' => 'Normal',
                'service_status' => 'Active',
                'service_period_start' => '2026-07-11',
                'service_period_end' => '2027-08-11',
                'entitlement' => 'standard',
                'package_status' => 'Active',
                'administrative_attention' => null,
                'public_warning_enabled' => 1,
                'ack_response' => 'manual_ack_required',
                'notes' => 'Demo warning station. Low-level ASCP behavior is not configured here.',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ],
            [
                'id' => 5,
                'workspace_id' => 4,
                'project_id' => 4,
                'monitoring_station_id' => 4,
                'station_code' => 'WS-SEMERU-DOWN',
                'name' => 'Semeru Downstream Warning Station',
                'zone_id' => 'ZONE-SEMERU-DOWN',
                'administrative_location' => 'Lumajang Downstream Warning Zone',
                'coordinate' => '-8.2410,113.0200',
                'latitude' => '-8.2410000',
                'longitude' => '113.0200000',
                'controller_id' => 'WSCP-SEMERU-DOWN',
                'controller_model' => 'Demo WSCP',
                'controller_vendor' => 'RESQ',
                'controller_status' => 'Standby',
                'registration_status' => 'registered',
                'registered_at' => '2026-07-27 08:00:00',
                'registered_by_user_id' => 1,
                'output_devices' => '["WSCP", "Siren", "Beacon"]',
                'status' => 'Normal',
                'service_status' => 'Active',
                'service_period_start' => '2026-07-11',
                'service_period_end' => '2027-08-11',
                'entitlement' => 'standard',
                'package_status' => 'Active',
                'administrative_attention' => null,
                'public_warning_enabled' => 1,
                'ack_response' => 'manual_ack_required',
                'notes' => 'Demo downstream warning station. Low-level output behavior remains outside Sentinel EMP.',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ]
        ];

        foreach ($data as $row) {
            DB::table('warning_stations')->insertOrIgnore($row);
        }
    }
}