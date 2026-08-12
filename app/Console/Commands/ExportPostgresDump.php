<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

class ExportPostgresDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export-postgres
                            {--output= : Output directory (default: public/db-export)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export database data from local MySQL to PostgreSQL-compatible SQL';

    /**
     * Columns to skip from every export.
     */
    private const SKIP_COLUMNS = [];

    /**
     * Tables to skip entirely.
     */
    private const SKIP_TABLES = [
        'migrations',
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

        $this->info("Exporting PostgreSQL-compatible data from local MySQL...");
        $this->newLine();

        $tables = $this->getTables();

        $outputFile = $outputDir . '/resq_postgres_data.sql';
        $handle = fopen($outputFile, 'w');

        fwrite($handle, "-- PostgreSQL data export\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Source: MySQL local database '" . config('database.connections.mysql.database') . "'\n");
        fwrite($handle, "\n");

        $totalRows = 0;
        $totalTables = 0;

        foreach ($tables as $table) {
            if (in_array($table, self::SKIP_TABLES)) {
                continue;
            }

            $columns = $this->getColumns($table);
            $rows = $this->fetchRows($table, $columns);

            if (empty($rows)) {
                continue;
            }

            fwrite($handle, "-- Table: {$table}\n");
            fwrite($handle, $this->buildInsertStatement($table, $columns, $rows));
            fwrite($handle, "\n\n");

            $totalRows += count($rows);
            $totalTables++;

            $this->line("  ✓ {$table}: " . count($rows) . " rows");
        }

        fclose($handle);

        $fileSize = filesize($outputFile);
        $fileSizeFormatted = $this->formatBytes($fileSize);

        $this->newLine();
        $this->info("PostgreSQL data export completed!");
        $this->info("  File: {$outputFile}");
        $this->info("  Size: {$fileSizeFormatted}");
        $this->info("  Tables: {$totalTables}");
        $this->info("  Total rows: {$totalRows}");
        $this->newLine();
        $this->info("Next steps:");
        $this->info("  1. Commit to git: git add public/db-export/");
        $this->info("  2. In production with PostgreSQL:");
        $this->info("     php artisan migrate --force");
        $this->info("     psql -h <host> -U <user> -d <db> -f public/db-export/resq_postgres_data.sql");

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

        // PostgreSQL uses TRUE/FALSE lowercase
        // MySQL uses 1/0 for booleans
        $booleanColumns = $this->detectBooleanColumns($table, $columns);

        $valueLines = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $col) {
                $val = $row->$col;

                if (is_null($val)) {
                    $values[] = 'NULL';
                } elseif (in_array($col, $booleanColumns)) {
                    // Convert MySQL boolean (0/1) to PostgreSQL TRUE/FALSE
                    $values[] = $val ? 'TRUE' : 'FALSE';
                } elseif (is_int($val) || is_float($val)) {
                    $values[] = (string) $val;
                } else {
                    // PostgreSQL string escaping: double single quotes, handle backslashes
                    $escaped = str_replace("'", "''", (string) $val);
                    // PostgreSQL interprets backslashes literally in standard strings
                    // but we keep them as-is since we're using standard '' quoting
                    $values[] = "'{$escaped}'";
                }
            }
            $valueLines[] = '(' . implode(', ', $values) . ')';
        }

        $sql = '';
        // Batch insert in chunks of 500 rows to keep statements manageable
        $chunks = array_chunk($valueLines, 500);
        foreach ($chunks as $chunk) {
            $sql .= "INSERT INTO \"{$table}\" ({$quotedColumns}) VALUES\n";
            $sql .= implode(",\n", $chunk) . ";\n\n";
        }

        return rtrim($sql);
    }

    /**
     * Detect boolean columns by checking column type.
     */
    private function detectBooleanColumns(string $table, array $columns): array
    {
        $types = DB::connection('mysql')
            ->select("SHOW COLUMNS FROM `{$table}`");

        $booleanColumns = [];
        foreach ($types as $col) {
            $type = strtolower($col->Type);
            // MySQL has TINYINT(1) for booleans
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
