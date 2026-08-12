<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferencePointsSeeder extends Seeder
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
                'point_code' => 'BM-DEMO-01',
                'name' => 'Semeru Reference BM 01',
                'point_type' => 'BM',
                'coordinate' => '-8.1080,112.9220',
                'latitude' => '-8.1080000',
                'longitude' => '112.9220000',
                'status' => 'Active',
                'notes' => 'Reference BM near Semeru source area for CFPE demo.',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('reference_points')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}