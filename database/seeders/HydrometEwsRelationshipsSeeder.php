<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HydrometEwsRelationshipsSeeder extends Seeder
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
                'corridor_id' => 4,
                'monitoring_station_id' => 4,
                'warning_station_id' => 4,
                'relationship_code' => 'EWS-DEMO-01',
                'name' => 'Demo Hydromet EWS Relationship',
                'status' => 'active',
                'notes' => 'Corridor + Monitoring Station + Warning Station demo relation.',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        foreach ($data as $row) {
            DB::table('hydromet_ews_relationships')->insertOrIgnore($row);
        }
    }
}