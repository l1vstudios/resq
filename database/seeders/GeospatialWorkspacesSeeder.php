<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeospatialWorkspacesSeeder extends Seeder
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
                'workspace_code' => 'GWS-EMP-DEMO',
                'name' => 'Semeru Geospatial Workspace',
                'hazard' => 'Hydromet',
                'province' => 'Jawa Timur',
                'city' => 'Lumajang',
                'beneficiaries' => 12000,
                'latitude' => '-8.1724000',
                'longitude' => '112.9716000',
                'status' => 'Normal',
                'basemap_provider' => 'OpenStreetMap',
                'basemap_tile_url' => null,
                'default_zoom' => 12,
                'map_bounds' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ]
        ];

        foreach ($data as $row) {
            DB::table('geospatial_workspaces')->insertOrIgnore($row);
        }
    }
}