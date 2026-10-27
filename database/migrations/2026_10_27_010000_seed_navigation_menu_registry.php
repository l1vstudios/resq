<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $menus = [
            ['dashboard', 'Dashboard', 'dashboard', 'dashboard', false, 10],
            ['monitoring', 'Monitoring', 'monitoring.index', 'monitoring', true, 20],
            ['project', 'Project', 'projects.index', 'project', true, 30],
            ['geospatial', 'Geospatial', 'project-setup.section', 'workspace', true, 40],
            ['monitoring-station', 'Monitoring Station', 'monitoring-stations.index', 'monitoring-station', true, 50],
            ['warning-station', 'Warning Station', 'warning-stations.index', 'warning-station', true, 60],
            ['sensor-data', 'Sensor & Data', 'sensors.index', 'sensor', true, 70],
            ['canonical-data', 'Canonical Data', 'canonical-database.index', 'canonical-data', true, 80],
            ['operations-response', 'Operational & Response', 'platform-operations.index', 'operations', true, 90],
            ['cfpe-corridor', 'CFPE Corridor', 'cfpe.index', 'cfpe', true, 100],
            ['data-loggers', 'Data Loggers', 'data-loggers.index', 'data-logger', true, 110],
            ['asset-management', 'Manajemen Assets', 'asset-management.index', 'asset', true, 120],
            ['master-data-tde', 'Master Data TDE', 'master-data-tde.index', 'tde', true, 130],
            ['tde-forecast', 'TDE Forecast', 'tde-forecast.index', 'tde-forecast', true, 140],
            ['prefix-sensors', 'Prefix Sensors', 'mst-prefixes.index', 'sensor-prefix', false, 150],
            ['rednode-pin-scan', 'RedNode Pin Scan', 'rednode-pin-scan.index', 'rednode-pin-scan', false, 160],
            ['rednode-builder', 'RedNode Builder', 'rednode-builder.index', 'rednode-builder', true, 170],
            ['production-guide', 'Panduan Production', 'production-guide.index', 'production-guide', false, 180],
            ['client-operational-state', 'Operational State', 'client-operations.index', 'operational-state', true, 190],
            ['client-operational-integrity', 'Operational Integrity', 'client-operations.index', 'operational-integrity', true, 200],
            ['client-administrative-monitoring', 'Administrative Monitoring', 'client-operations.index', 'administrative-monitoring', true, 210],
            ['client-function-configuration', 'Function Configuration', 'client-operations.function-configuration.index', 'function-configuration', true, 220],
            ['client-reporting', 'Reporting & Export', 'client-operations.reporting.index', 'reporting', true, 230],
            ['client-inbox', 'Inbox', 'client-operations.inbox.index', 'inbox', true, 240],
            ['client-users', 'Client Users', 'client-operations.users.index', 'client-user', true, 250],
            ['admin-management', 'Admin Management', 'admins.index', 'admin-management', false, 260],
        ];

        DB::table('navigation_menus')->insertOrIgnore(collect($menus)->map(fn (array $menu) => [
            'code' => $menu[0],
            'label' => $menu[1],
            'route_name' => $menu[2],
            'resource' => $menu[3],
            'is_project_scoped' => $menu[4],
            'sort_order' => $menu[5],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    public function down(): void
    {
        DB::table('navigation_menus')->whereIn('code', [
            'dashboard', 'monitoring', 'project', 'geospatial', 'monitoring-station', 'warning-station', 'sensor-data',
            'canonical-data', 'operations-response', 'cfpe-corridor', 'data-loggers', 'asset-management', 'master-data-tde',
            'tde-forecast', 'prefix-sensors', 'rednode-pin-scan', 'rednode-builder', 'production-guide',
            'client-operational-state', 'client-operational-integrity', 'client-administrative-monitoring',
            'client-function-configuration', 'client-reporting', 'client-inbox', 'client-users', 'admin-management',
        ])->delete();
    }
};
