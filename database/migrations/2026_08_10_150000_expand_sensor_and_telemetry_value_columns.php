<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('sensors') && Schema::hasColumn('sensors', 'value')) {
            DB::statement('ALTER TABLE `sensors` MODIFY `value` TEXT NULL');
        }

        if (Schema::hasTable('telemetry_readings') && Schema::hasColumn('telemetry_readings', 'value')) {
            DB::statement('ALTER TABLE `telemetry_readings` MODIFY `value` TEXT NULL');
        }

        if (Schema::hasTable('telemetry_readings') && Schema::hasColumn('telemetry_readings', 'raw_value')) {
            DB::statement('ALTER TABLE `telemetry_readings` MODIFY `raw_value` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('sensors') && Schema::hasColumn('sensors', 'value')) {
            DB::statement('ALTER TABLE `sensors` MODIFY `value` VARCHAR(255) NULL');
        }

        if (Schema::hasTable('telemetry_readings') && Schema::hasColumn('telemetry_readings', 'value')) {
            DB::statement('ALTER TABLE `telemetry_readings` MODIFY `value` VARCHAR(255) NULL');
        }

        if (Schema::hasTable('telemetry_readings') && Schema::hasColumn('telemetry_readings', 'raw_value')) {
            DB::statement('ALTER TABLE `telemetry_readings` MODIFY `raw_value` VARCHAR(255) NULL');
        }
    }
};
