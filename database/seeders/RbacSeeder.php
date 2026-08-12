<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // Create Permissions for Project Configuration
        $projectCreatePerm = Permission::firstOrCreate(
            ['name' => 'project.create'],
            [
                'display_name' => 'Create Project',
                'description' => 'Can create new projects',
                'resource' => 'project',
                'action' => 'create',
            ]
        );

        $projectEditPerm = Permission::firstOrCreate(
            ['name' => 'project.edit'],
            [
                'display_name' => 'Edit Project',
                'description' => 'Can edit existing projects',
                'resource' => 'project',
                'action' => 'edit',
            ]
        );

        $projectDeletePerm = Permission::firstOrCreate(
            ['name' => 'project.delete'],
            [
                'display_name' => 'Delete Project',
                'description' => 'Can delete projects',
                'resource' => 'project',
                'action' => 'delete',
            ]
        );

        // Create Permissions for Platform Operations
        Permission::firstOrCreate(
            ['name' => 'operational-state.access'],
            [
                'display_name' => 'Access Operational State',
                'description' => 'Can view operational state monitoring',
                'resource' => 'operational-state',
                'action' => 'access',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'operational-integrity.access'],
            [
                'display_name' => 'Access Operational Integrity',
                'description' => 'Can view operational integrity status',
                'resource' => 'operational-integrity',
                'action' => 'access',
            ]
        );

        $reportingPerm = Permission::firstOrCreate(
            ['name' => 'reporting.access'],
            [
                'display_name' => 'Access Reporting',
                'description' => 'Can access reporting and analytics',
                'resource' => 'reporting',
                'action' => 'access',
            ]
        );

        Permission::firstOrCreate(
            ['name' => 'administrative-monitoring.access'],
            [
                'display_name' => 'Access Administrative Monitoring',
                'description' => 'Can view administrative monitoring state',
                'resource' => 'administrative-monitoring',
                'action' => 'access',
            ]
        );

        // Create Sentinel Roles
        $sentinelAdminRole = Role::firstOrCreate(
            ['name' => 'SentinelAdmin'],
            [
                'display_name' => 'Sentinel Administrator',
                'description' => 'Full administrative access to Sentinel Console',
                'type' => 'system',
            ]
        );

        $sentinelAdminRole->permissions()->syncWithoutDetaching([
            $projectCreatePerm->id,
            $projectEditPerm->id,
            $projectDeletePerm->id,
            Permission::where('name', 'operational-state.access')->first()?->id,
            Permission::where('name', 'operational-integrity.access')->first()?->id,
            Permission::where('name', 'administrative-monitoring.access')->first()?->id,
            $reportingPerm->id,
        ]);

        $projectManagerRole = Role::firstOrCreate(
            ['name' => 'ProjectManager'],
            [
                'display_name' => 'Project Manager',
                'description' => 'Can manage project configurations',
                'type' => 'system',
            ]
        );

        $projectManagerRole->permissions()->syncWithoutDetaching([
            $projectEditPerm->id,
        ]);

        $platformOperatorRole = Role::firstOrCreate(
            ['name' => 'PlatformOperator'],
            [
                'display_name' => 'Platform Operator',
                'description' => 'Can monitor operational state and integrity',
                'type' => 'system',
            ]
        );

        $platformOperatorRole->permissions()->syncWithoutDetaching([
            Permission::where('name', 'operational-state.access')->first()?->id,
            Permission::where('name', 'operational-integrity.access')->first()?->id,
            Permission::where('name', 'administrative-monitoring.access')->first()?->id,
        ]);

        // Create Client Roles
        Role::firstOrCreate(
            ['name' => 'ClientAdmin'],
            [
                'display_name' => 'Client Administrator',
                'description' => 'Administrator for a client organization',
                'type' => 'system',
            ]
        );

        Role::firstOrCreate(
            ['name' => 'ClientOperator'],
            [
                'display_name' => 'Client Operator',
                'description' => 'Operator for client projects',
                'type' => 'system',
            ]
        );

        Role::firstOrCreate(
            ['name' => 'ClientViewer'],
            [
                'display_name' => 'Client Viewer',
                'description' => 'Read-only access for client projects',
                'type' => 'system',
            ]
        );
    }
}
