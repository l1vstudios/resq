<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connectivity_configs', function (Blueprint $table) {
            $table->string('serial_port')->nullable()->after('gateway_id');
            $table->unsignedInteger('baud_rate')->nullable()->after('serial_port');
            $table->unsignedTinyInteger('data_bits')->nullable()->after('baud_rate');
            $table->unsignedTinyInteger('stop_bits')->nullable()->after('data_bits');
            $table->string('parity', 20)->nullable()->after('stop_bits');
            $table->unsignedInteger('timeout_ms')->nullable()->after('parity');
            $table->string('pin_mapping')->nullable()->after('timeout_ms');
        });
    }

    public function down(): void
    {
        Schema::table('connectivity_configs', function (Blueprint $table) {
            $table->dropColumn([
                'serial_port',
                'baud_rate',
                'data_bits',
                'stop_bits',
                'parity',
                'timeout_ms',
                'pin_mapping',
            ]);
        });
    }
};
