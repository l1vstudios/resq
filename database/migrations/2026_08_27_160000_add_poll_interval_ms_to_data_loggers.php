<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_loggers', function (Blueprint $table) {
            if (! Schema::hasColumn('data_loggers', 'poll_interval_ms')) {
                $table->unsignedInteger('poll_interval_ms')->default(2000)->after('logger_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_loggers', function (Blueprint $table) {
            $table->dropColumn('poll_interval_ms');
        });
    }
};
