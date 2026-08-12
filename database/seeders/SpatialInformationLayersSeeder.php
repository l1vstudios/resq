<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpatialInformationLayersSeeder extends Seeder
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
                'layer_code' => 'LAYER-DEMO-FLOODPLAIN',
                'name' => 'Semeru Lahar Information Layer',
                'layer_type' => 'GeoJSON',
                'source_url' => null,
                'layer_payload' => '{"type": "FeatureCollection", "features": []}',
                'style_color' => '#2563eb',
                'visible_by_default' => 1,
                'sort_order' => 1,
                'status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        foreach ($data as $row) {
            DB::table('spatial_information_layers')->insertOrIgnore($row);
        }
    }
}