<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            if (! Schema::hasColumn('sensors', 'data_logger_id')) {
                $table->foreignId('data_logger_id')
                    ->nullable()
                    ->after('monitoring_station_id')
                    ->constrained('data_loggers')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('sensors', 'data_logger_id') && Schema::hasTable('data_loggers')) {
            DB::table('sensors')
                ->whereNull('data_logger_id')
                ->orderBy('id')
                ->get()
                ->each(function ($sensor) {
                    $loggerId = DB::table('data_loggers')
                        ->where('monitoring_station_id', $sensor->monitoring_station_id)
                        ->orderBy('id')
                        ->value('id');

                    if ($loggerId) {
                        DB::table('sensors')
                            ->where('id', $sensor->id)
                            ->update(['data_logger_id' => $loggerId]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            if (Schema::hasColumn('sensors', 'data_logger_id')) {
                $table->dropConstrainedForeignId('data_logger_id');
            }
        });
    }
};
