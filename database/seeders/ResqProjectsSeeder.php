<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResqProjectsSeeder extends Seeder
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
                'project_code' => 'EMP-DEMO-001',
                'name' => 'Sentinal Project',
                'owner' => 'Sentinel Demo',
                'project_date' => '2026-08-11',
                'status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02',
                'client_id' => 4
            ]
        ];

        foreach ($data as $row) {
            DB::table('resq_projects')->insertOrIgnore($row);
        }
    }
}