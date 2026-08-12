<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateDataSeeders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-data-seeders {--table= : Specific table to generate} {--force : Overwrite existing seeders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate seeder files for all tables with current data';

    /**
     * Tables that should be skipped (system tables and runtime data)
     */
    protected $skipTables = [
        'migrations',
        'failed_jobs',
        'password_resets',
        'personal_access_tokens',
        // Runtime/telemetry data - skip by default (can use --include-telemetry to include)
        'telemetry_readings',
        'canonical_observations',
        'canonical_parameter_values',
        'raw_data_ingestions',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = $this->getTablesSortedByDependencies();
        $specificTable = $this->option('table');

        if ($specificTable) {
            $tables = [$specificTable];
        }

        $generatedSeeders = [];

        foreach ($tables as $table) {
            if (in_array($table, $this->skipTables)) {
                $this->warn("Skipping system table: {$table}");
                continue;
            }

            $this->info("Processing table: {$table}");

            $count = DB::table($table)->count();

            if ($count === 0) {
                $this->warn("  - Table is empty, skipping...");
                continue;
            }

            $this->info("  - Found {$count} rows");

            $seederName = $this->generateSeeder($table);
            $generatedSeeders[] = $seederName;
        }

        if (!$specificTable) {
            $this->updateDatabaseSeeder($generatedSeeders);
        }

        $this->info("\n✓ Generated " . count($generatedSeeders) . " seeder(s)");
        $this->info("Run 'php artisan db:seed' to execute all seeders");
    }

    /**
     * Get all tables sorted by foreign key dependencies
     */
    protected function getTablesSortedByDependencies(): array
    {
        $allTables = $this->getTables();
        $sorted = [];
        $visited = [];

        // Build dependency graph
        $dependencies = [];
        foreach ($allTables as $table) {
            $foreignKeys = $this->getForeignKeys($table);
            $dependencies[$table] = $foreignKeys;
        }

        // Topological sort
        $visit = function ($table) use (&$visit, &$sorted, &$visited, $dependencies, $allTables) {
            if (in_array($table, $visited)) {
                return;
            }
            $visited[] = $table;

            foreach ($dependencies[$table] as $dep) {
                if (in_array($dep, $allTables)) {
                    $visit($dep);
                }
            }

            $sorted[] = $table;
        };

        foreach ($allTables as $table) {
            $visit($table);
        }

        return $sorted;
    }

    /**
     * Get foreign key references for a table
     */
    protected function getForeignKeys(string $table): array
    {
        $databaseName = DB::getDatabaseName();
        $foreignKeys = DB::select("
            SELECT
                REFERENCED_TABLE_NAME as referenced_table
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$databaseName, $table]);

        return array_map(function ($fk) {
            return $fk->referenced_table;
        }, $foreignKeys);
    }

    /**
     * Get all tables from the database
     */
    protected function getTables(): array
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $key = "Tables_in_{$databaseName}";

        return array_map(function ($table) use ($key) {
            return $table->$key;
        }, $tables);
    }

    /**
     * Generate a seeder file for a specific table
     */
    protected function generateSeeder(string $table): string
    {
        $columns = $this->getTableColumns($table);
        $data = DB::table($table)->orderBy('id')->get();

        $seederName = Str::studly($table) . 'Seeder';
        $className = $seederName;

        $content = $this->buildSeederContent($className, $table, $data);

        $path = database_path("seeders/{$className}.php");

        if (file_exists($path) && !$this->option('force')) {
            $this->warn("  - Seeder already exists, use --force to overwrite");
            return $seederName;
        }

        file_put_contents($path, $content);
        $this->info("  - Created: database/seeders/{$className}.php");

        return $seederName;
    }

    /**
     * Get table columns information
     */
    protected function getTableColumns(string $table): array
    {
        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");

        $columnInfo = [];
        foreach ($columns as $column) {
            $columnInfo[] = [
                'name' => $column->Field,
                'type' => $column->Type,
                'null' => $column->Null === 'YES',
            ];
        }

        return $columnInfo;
    }

    /**
     * Build the seeder file content
     */
    protected function buildSeederContent(string $className, string $table, $data): string
    {
        $rows = [];

        foreach ($data as $row) {
            $rowArray = (array) $row;
            $rows[] = $rowArray;
        }

        $rowsPhp = $this->generatePhpArray($rows, 8);

        return <<<PHP
<?php

namespace Database\\Seeders;

use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;

class {$className} extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \$data = {$rowsPhp};

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (\$data as \$row) {
            DB::table('{$table}')->insertOrIgnore(\$row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
PHP;
    }

    /**
     * Generate PHP array string from data
     */
    protected function generatePhpArray(array $data, int $indentLevel = 1): string
    {
        if (empty($data)) {
            return '[]';
        }

        // Check if this is a list of records or a simple associative array
        $isRecordList = false;
        $firstElement = reset($data);
        if (is_array($firstElement) || is_object($firstElement)) {
            $isRecordList = true;
        }

        $indent = str_repeat(' ', $indentLevel);
        $itemIndent = str_repeat(' ', $indentLevel + 4);
        $lines = [];

        $lines[] = '[';

        foreach ($data as $rowIndex => $row) {
            if (!is_array($row) && !is_object($row)) {
                // Simple value, not a record
                $formattedValue = $this->formatPhpValue($row, $indentLevel + 4);
                $lines[] = $itemIndent . $formattedValue;
                if ($rowIndex !== count($data) - 1) {
                    $lines[count($lines) - 1] .= ',';
                }
                continue;
            }

            $lines[] = $itemIndent . '[';

            $rowLines = [];
            foreach ($row as $key => $value) {
                $formattedValue = $this->formatPhpValue($value, $indentLevel + 8);
                $rowLines[] = $itemIndent . "    '{$key}' => {$formattedValue}";
            }

            $lines[] = implode(",\n", $rowLines);

            if ($rowIndex === count($data) - 1) {
                $lines[] = $itemIndent . ']';
            } else {
                $lines[] = $itemIndent . '],';
            }
        }

        $lines[] = $indent . ']';

        return implode("\n", $lines);
    }

    /**
     * Format a value as PHP code
     */
    protected function formatPhpValue($value, int $indentLevel = 1): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            // var_export preserves JSON quotes in single-quoted PHP literals.
            return var_export($value, true);
        }

        if (is_array($value) || is_object($value)) {
            return var_export(
                json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                true
            );
        }

        return var_export((string) $value, true);
    }

    /**
     * Update DatabaseSeeder to call all generated seeders
     */
    protected function updateDatabaseSeeder(array $seederNames): void
    {
        $seederPath = database_path('seeders/DatabaseSeeder.php');

        // Build the use statements
        $useStatements = "use Illuminate\\Database\\Seeder;\n";
        foreach ($seederNames as $seeder) {
            $useStatements .= "use Database\\Seeders\\{$seeder};\n";
        }

        // Build the call statements
        $callStatements = '';
        foreach ($seederNames as $seeder) {
            $callStatements .= "        \$this->call({$seeder}::class);\n";
        }

        $newContent = <<<PHP
<?php

namespace Database\\Seeders;

{$useStatements}

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
{$callStatements}    }
}
PHP;

        file_put_contents($seederPath, $newContent);
        $this->info("\n✓ Updated DatabaseSeeder.php");
    }
}
