<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
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
                'id' => 10,
                'name' => 'SentinelAdmin',
                'display_name' => 'Sentinel Administrator',
                'description' => 'Full administrative access to Sentinel Console',
                'type' => 'system',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 11,
                'name' => 'ProjectManager',
                'display_name' => 'Project Manager',
                'description' => 'Can manage project configurations',
                'type' => 'system',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 12,
                'name' => 'PlatformOperator',
                'display_name' => 'Platform Operator',
                'description' => 'Can monitor operational state and integrity',
                'type' => 'system',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 13,
                'name' => 'ClientAdmin',
                'display_name' => 'Client Administrator',
                'description' => 'Administrator for a client organization',
                'type' => 'system',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 14,
                'name' => 'ClientOperator',
                'display_name' => 'Client Operator',
                'description' => 'Operator for client projects',
                'type' => 'system',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 15,
                'name' => 'ClientViewer',
                'display_name' => 'Client Viewer',
                'description' => 'Read-only access for client projects',
                'type' => 'system',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('roles')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}