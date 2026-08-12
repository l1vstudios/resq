<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telemetry_readings', function (Blueprint $table) {
            $table->index(['sensor_id', 'received_at', 'id'], 'telemetry_sensor_received_idx');
            $table->index(['data_logger_id', 'received_at'], 'telemetry_logger_received_idx');
        });

        Schema::table('sensors', function (Blueprint $table) {
            $table->index(['monitoring_station_id', 'last_seen_at'], 'sensors_station_last_seen_idx');
            $table->index(['workspace_id', 'status'], 'sensors_workspace_status_idx');
        });

        Schema::table('connectivity_configs', function (Blueprint $table) {
            $table->index(['data_logger_id', 'last_seen_at'], 'connectivity_logger_last_seen_idx');
        });

        Schema::table('monitoring_stations', function (Blueprint $table) {
            $table->string('service_status')->nullable()->after('status');
            $table->date('service_period_start')->nullable()->after('service_status');
            $table->date('service_period_end')->nullable()->after('service_period_start');
            $table->string('entitlement')->nullable()->after('service_period_end');
            $table->string('package_status')->nullable()->after('entitlement');
            $table->text('administrative_attention')->nullable()->after('package_status');
            $table->index(['project_id', 'service_status'], 'monitoring_project_service_idx');
        });

        Schema::table('warning_stations', function (Blueprint $table) {
            $table->string('service_status')->nullable()->after('status');
            $table->date('service_period_start')->nullable()->after('service_status');
            $table->date('service_period_end')->nullable()->after('service_period_start');
            $table->string('entitlement')->nullable()->after('service_period_end');
            $table->string('package_status')->nullable()->after('entitlement');
            $table->text('administrative_attention')->nullable()->after('package_status');
            $table->index(['project_id', 'service_status'], 'warning_project_service_idx');
        });
    }

    public function down(): void
    {
        Schema::table('warning_stations', function (Blueprint $table) {
            $table->dropIndex('warning_project_service_idx');
            $table->dropColumn([
                'service_status',
                'service_period_start',
                'service_period_end',
                'entitlement',
                'package_status',
                'administrative_attention',
            ]);
        });

        Schema::table('monitoring_stations', function (Blueprint $table) {
            $table->dropIndex('monitoring_project_service_idx');
            $table->dropColumn([
                'service_status',
                'service_period_start',
                'service_period_end',
                'entitlement',
                'package_status',
                'administrative_attention',
            ]);
        });

        Schema::table('connectivity_configs', function (Blueprint $table) {
            $table->dropIndex('connectivity_logger_last_seen_idx');
        });

        Schema::table('sensors', function (Blueprint $table) {
            $table->dropIndex('sensors_station_last_seen_idx');
            $table->dropIndex('sensors_workspace_status_idx');
        });

        Schema::table('telemetry_readings', function (Blueprint $table) {
            $table->dropIndex('telemetry_sensor_received_idx');
            $table->dropIndex('telemetry_logger_received_idx');
        });
    }
};
