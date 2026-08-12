<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MonitoringStationsSeeder extends Seeder
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
                'corridor_id' => 4,
                'station_code' => 'MS-DEMO-01',
                'name' => 'Demo Monitoring Station 01',
                'station_type' => 'hydromet_monitoring',
                'coordinate' => '-8.1724,112.9716',
                'latitude' => '-8.1724000',
                'longitude' => '112.9716000',
                'logger_id' => null,
                'logger_status' => 'Active',
                'connectivity_status' => 'Online',
                'registration_status' => 'registered',
                'registered_at' => '2026-07-22 08:00:00',
                'registered_by_user_id' => 1,
                'status' => 'Normal',
                'service_status' => 'Active',
                'service_period_start' => '2026-07-11',
                'service_period_end' => '2027-08-11',
                'entitlement' => 'standard',
                'package_status' => 'Active',
                'administrative_attention' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ],
            [
                'id' => 5,
                'workspace_id' => 4,
                'project_id' => 4,
                'corridor_id' => 4,
                'station_code' => 'MS-SEMERU-UPPER',
                'name' => 'Semeru Upper Monitoring Station',
                'station_type' => 'hydromet_monitoring',
                'coordinate' => '-8.1378,112.9467',
                'latitude' => '-8.1378000',
                'longitude' => '112.9467000',
                'logger_id' => null,
                'logger_status' => 'Registered',
                'connectivity_status' => 'Pending',
                'registration_status' => 'registered',
                'registered_at' => '2026-07-24 08:00:00',
                'registered_by_user_id' => 1,
                'status' => 'Normal',
                'service_status' => 'Active',
                'service_period_start' => '2026-07-11',
                'service_period_end' => '2027-08-11',
                'entitlement' => 'standard',
                'package_status' => 'Active',
                'administrative_attention' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ],
            [
                'id' => 6,
                'workspace_id' => 4,
                'project_id' => 4,
                'corridor_id' => 4,
                'station_code' => 'MS-SEMERU-DOWN',
                'name' => 'Semeru Downstream Monitoring Station',
                'station_type' => 'hydromet_monitoring',
                'coordinate' => '-8.2390,113.0185',
                'latitude' => '-8.2390000',
                'longitude' => '113.0185000',
                'logger_id' => null,
                'logger_status' => 'Registered',
                'connectivity_status' => 'Pending',
                'registration_status' => 'registered',
                'registered_at' => '2026-07-24 08:00:00',
                'registered_by_user_id' => 1,
                'status' => 'Normal',
                'service_status' => 'Active',
                'service_period_start' => '2026-07-11',
                'service_period_end' => '2027-08-11',
                'entitlement' => 'standard',
                'package_status' => 'Active',
                'administrative_attention' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ]
        ];

        foreach ($data as $row) {
            DB::table('monitoring_stations')->insertOrIgnore($row);
        }
    }
}