<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telemetry_readings') || Schema::hasColumn('telemetry_readings', 'parameter_values')) {
            return;
        }

        Schema::table('telemetry_readings', function (Blueprint $table) {
            $table->json('parameter_values')->nullable()->after('value');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('telemetry_readings') || ! Schema::hasColumn('telemetry_readings', 'parameter_values')) {
            return;
        }

        Schema::table('telemetry_readings', function (Blueprint $table) {
            $table->dropColumn('parameter_values');
        });
    }
};
