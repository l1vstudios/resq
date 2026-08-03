<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connectivity_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('connectivity_configs', 'serial_settings')) {
                $table->json('serial_settings')->nullable()->after('connectivity_status');
            }

            if (! Schema::hasColumn('connectivity_configs', 'runtime_state')) {
                $table->json('runtime_state')->nullable()->after('serial_settings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('connectivity_configs', function (Blueprint $table) {
            if (Schema::hasColumn('connectivity_configs', 'runtime_state')) {
                $table->dropColumn('runtime_state');
            }

            if (Schema::hasColumn('connectivity_configs', 'serial_settings')) {
                $table->dropColumn('serial_settings');
            }
        });
    }
};
