<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionDataSeeder extends Seeder
{
    /**
     * Seed the application's database with data from local export.
     *
     * Usage: php artisan db:seed --class=ProductionDataSeeder
     *
     * This seeder imports data from storage/app/db-export/resq_data_only.sql
     * which is exported from the local development database.
     *
     * @return void
     */
    public function run()
    {
        $dumpFile = storage_path('app/db-export/resq_data_only.sql');

        if (!file_exists($dumpFile)) {
            $this->command->error("Data dump file not found: {$dumpFile}");
            $this->command->info("Please run the following command to export data from local database:");
            $this->command->info("mysqldump --protocol=TCP -h 127.0.0.1 -P 3306 -u root --no-create-info --set-gtid-purged=OFF --complete-insert --default-character-set=utf8mb4 resq > storage/app/db-export/resq_data_only.sql");
            return;
        }

        $this->command->info("Loading data from: {$dumpFile}");

        // Temporarily disable foreign key checks to allow data import
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Read SQL file
            $sql = file_get_contents($dumpFile);

            // Split by statements (simple approach)
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function ($statement) {
                    // Filter out comments and empty lines
                    $statement = trim($statement);
                    return !empty($statement) && !str_starts_with($statement, '--') && !str_starts_with($statement, '/*!');
                }
            );

            $executedCount = 0;
            $skippedCount = 0;

            foreach ($statements as $statement) {
                try {
                    // Skip LOCK/UNLOCK and ALTER statements
                    if (str_contains(strtoupper($statement), 'LOCK TABLES') ||
                        str_contains(strtoupper($statement), 'UNLOCK TABLES') ||
                        str_contains(strtoupper($statement), 'ALTER TABLE')) {
                        $skippedCount++;
                        continue;
                    }

                    DB::statement($statement);
                    $executedCount++;
                } catch (\Exception $e) {
                    $this->command->warn("Skipped statement: " . substr($statement, 0, 100) . "...");
                    $this->command->warn("Reason: " . $e->getMessage());
                    $skippedCount++;
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->command->info("Data import completed!");
            $this->command->info("Executed: {$executedCount} statements");
            $this->command->info("Skipped: {$skippedCount} statements");

            // Verify data was imported
            $this->verifyDataImport();

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->command->error("Error importing data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify that data was imported successfully.
     */
    private function verifyDataImport()
    {
        $this->command->newLine();
        $this->command->info("Verifying data import...");

        $database = config('database.connections.mysql.database');

        $tables = DB::select("
            SELECT TABLE_NAME, TABLE_ROWS
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?
            ORDER BY TABLE_NAME
        ", [$database]);

        $this->command->newLine();
        $this->command->info("Table Statistics:");
        $this->command->info(sprintf("%-40s %s", "Table Name", "Rows"));
        $this->command->info(str_repeat("-", 50));

        $totalRows = 0;
        foreach ($tables as $table) {
            $this->command->info(sprintf("%-40s %d", $table->TABLE_NAME, $table->TABLE_ROWS));
            $totalRows += $table->TABLE_ROWS;
        }

        $this->command->info(str_repeat("-", 50));
        $this->command->info(sprintf("%-40s %d", "TOTAL ROWS", $totalRows));
        $this->command->newLine();
    }
}
