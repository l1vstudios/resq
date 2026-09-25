<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sensors')) {
            return;
        }

        Schema::table('sensors', function (Blueprint $table) {
            if (! Schema::hasColumn('sensors', 'coordinate')) {
                $table->string('coordinate')->nullable();
            }

            if (! Schema::hasColumn('sensors', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }

            if (! Schema::hasColumn('sensors', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
        });

        $hasWarningStation = Schema::hasColumn('sensors', 'warning_station_id') && Schema::hasTable('warning_stations');
        $hasDataLogger = Schema::hasColumn('sensors', 'data_logger_id') && Schema::hasTable('data_loggers');
        $hasMonitoringStation = Schema::hasColumn('sensors', 'monitoring_station_id') && Schema::hasTable('monitoring_stations');

        if (! $hasMonitoringStation) {
            return;
        }

        DB::table('sensors')
            ->orderBy('id')
            ->get()
            ->each(function ($sensor, int $index) use ($hasWarningStation, $hasDataLogger) {
                $updates = [];

                if ($hasWarningStation && empty($sensor->warning_station_id) && ! empty($sensor->monitoring_station_id)) {
                    $warningStationId = DB::table('warning_stations')
                        ->where('monitoring_station_id', $sensor->monitoring_station_id)
                        ->when(! empty($sensor->workspace_id), fn ($query) => $query->where('workspace_id', $sensor->workspace_id))
                        ->orderBy('id')
                        ->value('id');

                    if ($warningStationId) {
                        $updates['warning_station_id'] = $warningStationId;
                        $sensor->warning_station_id = $warningStationId;
                    }
                }

                if ($hasDataLogger && empty($sensor->data_logger_id) && ! empty($sensor->monitoring_station_id)) {
                    $dataLoggerId = DB::table('data_loggers')
                        ->where('monitoring_station_id', $sensor->monitoring_station_id)
                        ->orderBy('id')
                        ->value('id');

                    if ($dataLoggerId) {
                        $updates['data_logger_id'] = $dataLoggerId;
                    }
                }

                if (empty($sensor->latitude) || empty($sensor->longitude)) {
                    $base = null;

                    if ($hasWarningStation && ! empty($sensor->warning_station_id)) {
                        $base = DB::table('warning_stations')
                            ->select(['latitude', 'longitude'])
                            ->where('id', $sensor->warning_station_id)
                            ->first();
                    }

                    if ((! $base || $base->latitude === null || $base->longitude === null) && ! empty($sensor->monitoring_station_id)) {
                        $base = DB::table('monitoring_stations')
                            ->select(['latitude', 'longitude'])
                            ->where('id', $sensor->monitoring_station_id)
                            ->first();
                    }

                    if ($base && $base->latitude !== null && $base->longitude !== null) {
                        $angle = deg2rad(($index % 8) * 45);
                        $distance = 0.0010 + (floor($index / 8) * 0.0005);
                        $latitude = round((float) $base->latitude + (cos($angle) * $distance), 7);
                        $longitude = round((float) $base->longitude + (sin($angle) * $distance), 7);

                        $updates['latitude'] = $latitude;
                        $updates['longitude'] = $longitude;
                        $updates['coordinate'] = sprintf('%.7F, %.7F', $latitude, $longitude);
                    }
                }

                if ($updates !== []) {
                    DB::table('sensors')->where('id', $sensor->id)->update($updates);
                }
            });
    }

    public function down(): void
    {
        // Backfill migration intentionally keeps existing sensor bindings and coordinates.
    }
};
