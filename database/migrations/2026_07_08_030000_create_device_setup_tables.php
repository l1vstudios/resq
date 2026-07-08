<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_loggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitoring_station_id')->nullable()->constrained('monitoring_stations')->nullOnDelete();
            $table->string('logger_code')->unique();
            $table->string('serial_number')->nullable();
            $table->string('logger_model')->nullable();
            $table->string('vendor')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('device_label')->nullable();
            $table->string('logger_status')->default('Active');
            $table->timestamps();
        });

        Schema::create('connectivity_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_logger_id')->constrained('data_loggers')->cascadeOnDelete();
            $table->string('connectivity_code')->unique();
            $table->string('communication_type')->nullable();
            $table->string('protocol')->nullable();
            $table->string('host_or_endpoint')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('topic_or_api_path')->nullable();
            $table->string('gateway_id')->nullable();
            $table->string('sim_number')->nullable();
            $table->string('imei')->nullable();
            $table->string('apn')->nullable();
            $table->string('connectivity_status')->default('Online');
            $table->timestamps();
        });

        Schema::create('device_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_logger_id')->constrained('data_loggers')->cascadeOnDelete();
            $table->string('credential_code')->unique();
            $table->string('device_token')->nullable();
            $table->string('mqtt_username')->nullable();
            $table->string('mqtt_password_hash')->nullable();
            $table->string('certificate_ref')->nullable();
            $table->string('credential_status')->default('Active');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('telemetry_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('sensors')->cascadeOnDelete();
            $table->foreignId('data_logger_id')->nullable()->constrained('data_loggers')->nullOnDelete();
            $table->string('value')->nullable();
            $table->string('alert_level')->default('Normal');
            $table->string('status')->default('Normal');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('monitoring_stations')) {
            DB::table('monitoring_stations')
                ->whereNotNull('logger_id')
                ->orderBy('id')
                ->get()
                ->each(function ($station) {
                    $logger = DB::table('data_loggers')->where('logger_code', $station->logger_id)->first();
                    $loggerId = $logger?->id ?? DB::table('data_loggers')->insertGetId([
                        'monitoring_station_id' => $station->id,
                        'logger_code' => $station->logger_id,
                        'serial_number' => $station->logger_id,
                        'logger_model' => 'Data Logger',
                        'device_label' => $station->logger_id,
                        'logger_status' => $station->logger_status ?? 'Active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('connectivity_configs')->insert([
                        'data_logger_id' => $loggerId,
                        'connectivity_code' => 'CONN-' . $station->station_code,
                        'topic_or_api_path' => 'telemetry/' . $station->station_code,
                        'connectivity_status' => $station->connectivity_status ?? 'Online',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('device_credentials')->insert([
                        'data_logger_id' => $loggerId,
                        'credential_code' => 'CRED-' . $station->station_code,
                        'mqtt_username' => strtolower(str_replace('-', '_', $station->station_code)),
                        'credential_status' => $station->logger_status ?? 'Active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_readings');
        Schema::dropIfExists('device_credentials');
        Schema::dropIfExists('connectivity_configs');
        Schema::dropIfExists('data_loggers');
    }
};
