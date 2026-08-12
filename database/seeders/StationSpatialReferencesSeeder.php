<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StationSpatialReferencesSeeder extends Seeder
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
                'workspace_id' => 4,
                'corridor_id' => 4,
                'reference_route_id' => 1,
                'reference_point_id' => 1,
                'monitoring_station_id' => 4,
                'warning_station_id' => null,
                'placement_role' => 'primary_monitoring',
                'station_offset' => 'chainage:125.75;offset:3.5m centerline',
                'status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 2,
                'project_id' => 4,
                'workspace_id' => 4,
                'corridor_id' => 4,
                'reference_route_id' => 1,
                'reference_point_id' => 1,
                'monitoring_station_id' => 5,
                'warning_station_id' => null,
                'placement_role' => 'corridor_monitoring',
                'station_offset' => 'chainage:1400;upper corridor monitoring point',
                'status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 3,
                'project_id' => 4,
                'workspace_id' => 4,
                'corridor_id' => 4,
                'reference_route_id' => 1,
                'reference_point_id' => 1,
                'monitoring_station_id' => 6,
                'warning_station_id' => null,
                'placement_role' => 'corridor_monitoring',
                'station_offset' => 'chainage:4600;downstream corridor monitoring point',
                'status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 4,
                'project_id' => 4,
                'workspace_id' => 4,
                'corridor_id' => 4,
                'reference_route_id' => 1,
                'reference_point_id' => 1,
                'monitoring_station_id' => null,
                'warning_station_id' => 4,
                'placement_role' => 'downstream_warning',
                'station_offset' => 'chainage:3100;downstream warning response point',
                'status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 5,
                'project_id' => 4,
                'workspace_id' => 4,
                'corridor_id' => 4,
                'reference_route_id' => 1,
                'reference_point_id' => 1,
                'monitoring_station_id' => null,
                'warning_station_id' => 5,
                'placement_role' => 'downstream_warning',
                'station_offset' => 'chainage:4600;downstream warning response point',
                'status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('station_spatial_references')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}