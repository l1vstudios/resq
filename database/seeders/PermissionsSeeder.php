<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
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
                'name' => 'project.create',
                'display_name' => 'Create Project',
                'description' => 'Can create new projects',
                'resource' => 'project',
                'action' => 'create',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 11,
                'name' => 'project.edit',
                'display_name' => 'Edit Project',
                'description' => 'Can edit existing projects',
                'resource' => 'project',
                'action' => 'edit',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 12,
                'name' => 'project.delete',
                'display_name' => 'Delete Project',
                'description' => 'Can delete projects',
                'resource' => 'project',
                'action' => 'delete',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 13,
                'name' => 'operational-state.access',
                'display_name' => 'Access Operational State',
                'description' => 'Can view operational state monitoring',
                'resource' => 'operational-state',
                'action' => 'access',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 14,
                'name' => 'operational-integrity.access',
                'display_name' => 'Access Operational Integrity',
                'description' => 'Can view operational integrity status',
                'resource' => 'operational-integrity',
                'action' => 'access',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 15,
                'name' => 'reporting.access',
                'display_name' => 'Access Reporting',
                'description' => 'Can access reporting and analytics',
                'resource' => 'reporting',
                'action' => 'access',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 16,
                'name' => 'administrative-monitoring.access',
                'display_name' => 'Access Administrative Monitoring',
                'description' => 'Can view administrative monitoring state',
                'resource' => 'administrative-monitoring',
                'action' => 'access',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        foreach ($data as $row) {
            DB::table('permissions')->insertOrIgnore($row);
        }
    }
}