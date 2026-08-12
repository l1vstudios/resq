<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportPostgresDumpOrdered extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export-postgres-ordered
                            {--output= : Output directory (default: public/db-export)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export database data from MySQL to PostgreSQL with proper foreign key ordering';

    /**
     * Tables to skip.
     */
    private const SKIP_TABLES = [
        'migrations',
        'password_resets',
        'failed_jobs',
        'personal_access_tokens',
    ];

    /**
     * Table dependency order (parent tables first).
     */
    private const TABLE_ORDER = [
        // Independent tables
        'roles',
        'permissions',
        'clients',
        'users',
        'provinces',
        'mst_prefixes',
        'resq_projects',
        'geospatial_workspaces',
        'reference_routes',
        'reference_points',

        // Depends on resq_projects
        'monitoring_stations',
        'warning_stations',
        'corridor_monitorings',
        'hydromet_ews_relationships',
        'hydromet_hazard_classifications',
        'hydromet_wdam_configs',
        'spatial_information_layers',
        'station_function_configurations',
        'station_spatial_references',
        'sentinel_notifications',

        // Depends on monitoring_stations
        'data_loggers',
        'data_logger_discoveries',

        // Depends on data_loggers, mst_prefixes
        'sensors',
        'connectivity_configs',

        // Depends on sensors
        'canonical_parameters',
        'canonical_parameter_values',
        'canonical_observations',
        'sensor_mapping_presets',
        'sensor_mapping_preset_items',
        'sensor_mapping_profiles',

        // RBAC relations
        'model_has_roles',
        'role_has_permissions',
        'user_has_projects',
        'project_recovery_accounts',

        // Other data tables
        'raw_data_ingestions',
        'telemetry_readings',
        'device_credentials',
        'geospatial_workspaces',
        'hydromet_wdam_configs',
        'warning_station_devices',
        'warning_station_device_heartbeats',
        'warning_station_telemetry_configs',
        'response_plans',
        'coridor_monitorings',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $outputDir = $this->option('output') ?? public_path('db-export');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->info("Exporting PostgreSQL-compatible data from local MySQL (with FK ordering)...");
        $this->newLine();

        $allTables = $this->getTables();
        $orderedTables = $this->orderTablesByDependency($allTables);

        $outputFile = $outputDir . '/resq_postgres_data_ordered.sql';
        $handle = fopen($outputFile, 'w');

        fwrite($handle, "-- PostgreSQL data export (with proper foreign key ordering)\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Source: MySQL local database '" . config('database.connections.mysql.database') . "'\n");
        fwrite($handle, "-- WARNING: Some tables may already have data from seeders. Run TRUNCATE first if needed.\n");
        fwrite($handle, "\n");
        fwrite($handle, "-- Disable foreign key checks during import\n");
        fwrite($handle, "SET CONSTRAINTS ALL DEFERRED;\n");
        fwrite($handle, "\n");

        $totalRows = 0;
        $totalTables = 0;

        foreach ($orderedTables as $table) {
            if (in_array($table, self::SKIP_TABLES)) {
                continue;
            }

            $columns = $this->getColumns($table);
            $rows = $this->fetchRows($table, $columns);

            if (empty($rows)) {
                continue;
            }

            fwrite($handle, "-- Table: {$table} (" . count($rows) . " rows)\n");
            fwrite($handle, $this->buildInsertStatement($table, $columns, $rows));
            fwrite($handle, "\n\n");

            $totalRows += count($rows);
            $totalTables++;

            $this->line("  ✓ {$table}: " . count($rows) . " rows");
        }

        fwrite($handle, "-- Re-enable foreign key checks\n");
        fwrite($handle, "SET CONSTRAINTS ALL IMMEDIATE;\n");

        fclose($handle);

        $fileSize = filesize($outputFile);
        $fileSizeFormatted = $this->formatBytes($fileSize);

        $this->newLine();
        $this->info("PostgreSQL data export (ordered) completed!");
        $this->info("  File: {$outputFile}");
        $this->info("  Size: {$fileSizeFormatted}");
        $this->info("  Tables: {$totalTables}");
        $this->info("  Total rows: {$totalRows}");
        $this->newLine();
        $this->warn("IMPORTANT: If production database already has seeded data:");
        $this->warn("  1. TRUNCATE tables first (in dependency order)");
        $this->warn("  2. Or use: php artisan migrate:refresh --force (drops and recreates)");
        $this->newLine();
        $this->info("Import command:");
        $this->info("  psql -h <host> -U <user> -d <db> -f {$outputFile}");

        return Command::SUCCESS;
    }

    /**
     * Get all table names from local MySQL database.
     */
    private function getTables(): array
    {
        $database = config('database.connections.mysql.database');
        $tables = DB::connection('mysql')
            ->select('SHOW TABLES');

        $key = 'Tables_in_' . $database;
        return array_map(fn($row) => $row->$key, $tables);
    }

    /**
     * Order tables by foreign key dependencies.
     */
    private function orderTablesByDependency(array $allTables): array
    {
        $ordered = [];

        // First, add tables from TABLE_ORDER that exist
        foreach (self::TABLE_ORDER as $table) {
            if (in_array($table, $allTables) && !in_array($table, $ordered)) {
                $ordered[] = $table;
            }
        }

        // Then, add any remaining tables not in TABLE_ORDER
        foreach ($allTables as $table) {
            if (!in_array($table, $ordered)) {
                $ordered[] = $table;
            }
        }

        return $ordered;
    }

    /**
     * Get column names for a table.
     */
    private function getColumns(string $table): array
    {
        $columns = DB::connection('mysql')
            ->select("SHOW COLUMNS FROM `{$table}`");

        return array_map(fn($col) => $col->Field, $columns);
    }

    /**
     * Fetch all rows from a table.
     */
    private function fetchRows(string $table, array $columns): array
    {
        $columnList = implode(', ', array_map(fn($c) => "`{$c}`", $columns));

        return DB::connection('mysql')
            ->select("SELECT {$columnList} FROM `{$table}`");
    }

    /**
     * Build PostgreSQL-compatible INSERT statement.
     */
    private function buildInsertStatement(string $table, array $columns, array $rows): string
    {
        $quotedColumns = implode(', ', array_map(fn($c) => "\"{$c}\"", $columns));
        $booleanColumns = $this->detectBooleanColumns($table, $columns);

        $valueLines = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $col) {
                $val = $row->$col;

                if (is_null($val)) {
                    $values[] = 'NULL';
                } elseif (in_array($col, $booleanColumns)) {
                    $values[] = $val ? 'TRUE' : 'FALSE';
                } elseif (is_int($val) || is_float($val)) {
                    $values[] = (string) $val;
                } else {
                    $escaped = str_replace("'", "''", (string) $val);
                    $values[] = "'{$escaped}'";
                }
            }
            $valueLines[] = '(' . implode(', ', $values) . ')';
        }

        $sql = '';
        $chunks = array_chunk($valueLines, 500);
        foreach ($chunks as $chunk) {
            $sql .= "INSERT INTO \"{$table}\" ({$quotedColumns}) VALUES\n";
            $sql .= implode(",\n", $chunk) . ";\n\n";
        }

        return rtrim($sql);
    }

    /**
     * Detect boolean columns.
     */
    private function detectBooleanColumns(string $table, array $columns): array
    {
        $types = DB::connection('mysql')
            ->select("SHOW COLUMNS FROM `{$table}`");

        $booleanColumns = [];
        foreach ($types as $col) {
            $type = strtolower($col->Type);
            if ($type === 'tinyint(1)' || str_starts_with($type, 'tinyint(1)')) {
                $booleanColumns[] = $col->Field;
            }
        }

        return $booleanColumns;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
