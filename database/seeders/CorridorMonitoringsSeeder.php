<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CorridorMonitoringsSeeder extends Seeder
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
                'project_id' => 4,
                'workspace_id' => 4,
                'reference_route_id' => 1,
                'corridor_code' => 'COR-DEMO-01',
                'name' => 'Semeru Lahar Corridor',
                'path_coordinates' => '[{"lat": -8.108, "lng": 112.922}, {"lat": -8.1378, "lng": 112.9467}, {"lat": -8.1724, "lng": 112.9716}, {"lat": -8.2052, "lng": 112.994}, {"lat": -8.239, "lng": 113.0185}]',
                'status' => 'Active',
                'status_metadata' => '{"hazard_state": "WASPADA"}',
                'notes' => 'Operational corridor from Semeru source area to downstream warning response points.',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        foreach ($data as $row) {
            DB::table('corridor_monitorings')->insertOrIgnore($row);
        }
    }
}