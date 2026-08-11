<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telemetry_readings')) {
            return;
        }

        Schema::table('telemetry_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('telemetry_readings', 'raw_value')) {
                $table->string('raw_value')->nullable()->after('value');
            }
            if (! Schema::hasColumn('telemetry_readings', 'numeric_value')) {
                $table->decimal('numeric_value', 16, 6)->nullable()->after('raw_value');
            }
            if (! Schema::hasColumn('telemetry_readings', 'registers')) {
                $table->json('registers')->nullable()->after('numeric_value');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('telemetry_readings')) {
            return;
        }

        Schema::table('telemetry_readings', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('telemetry_readings', 'raw_value')) {
                $columns[] = 'raw_value';
            }
            if (Schema::hasColumn('telemetry_readings', 'numeric_value')) {
                $columns[] = 'numeric_value';
            }
            if (Schema::hasColumn('telemetry_readings', 'registers')) {
                $columns[] = 'registers';
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
