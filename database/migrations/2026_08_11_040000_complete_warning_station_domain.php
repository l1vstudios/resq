<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('warning_stations', 'project_id')) {
            Schema::table('warning_stations', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('workspace_id')->constrained('resq_projects')->nullOnDelete();
                $table->string('administrative_location')->nullable()->after('zone_id');
                $table->text('notes')->nullable()->after('ack_response');
                $table->string('registration_status')->default('registered')->after('controller_status');
                $table->timestamp('registered_at')->nullable()->after('registration_status');
                $table->foreignId('registered_by_user_id')->nullable()->after('registered_at')->constrained('users')->nullOnDelete();

                $table->index(['project_id', 'registration_status'], 'warning_project_registration_idx');
                $table->index(['project_id', 'status'], 'warning_project_status_idx');
            });

            DB::table('warning_stations')
                ->orderBy('id')
                ->get()
                ->each(function ($station) {
                    $projectId = DB::table('geospatial_workspaces')
                        ->where('id', $station->workspace_id)
                        ->value('project_id');

                    DB::table('warning_stations')
                        ->where('id', $station->id)
                        ->update([
                            'project_id' => $projectId,
                            'registered_at' => $station->created_at ?? now(),
                        ]);
                });
        }

        if (! Schema::hasTable('warning_station_telemetry_configs')) {
            Schema::create('warning_station_telemetry_configs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
                $table->foreignId('warning_station_id')->constrained('warning_stations')->cascadeOnDelete();
                $table->string('config_code')->unique();
                $table->string('broker_config_ref')->nullable();
                $table->string('protocol')->default('MQTT');
                $table->string('host_or_endpoint')->nullable();
                $table->unsignedInteger('port')->nullable();
                $table->string('topic');
                $table->unsignedTinyInteger('qos')->default(0);
                $table->boolean('retain')->default(false);
                $table->string('credential_ref')->nullable();
                $table->string('connection_status')->default('unknown');
                $table->timestamp('last_connected_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'warning_station_id'], 'wstc_project_station_idx');
                $table->index(['connection_status', 'last_seen_at'], 'wstc_status_seen_idx');
            });
        }

        if (! Schema::hasTable('warning_station_devices')) {
            Schema::create('warning_station_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
                $table->foreignId('warning_station_id')->constrained('warning_stations')->cascadeOnDelete();
                $table->string('device_code')->unique();
                $table->string('device_type');
                $table->string('name')->nullable();
                $table->string('vendor')->nullable();
                $table->string('model')->nullable();
                $table->string('serial_number')->nullable();
                $table->boolean('expected')->default(true);
                $table->string('availability_state')->default('unknown');
                $table->string('health_state')->default('unknown');
                $table->timestamp('last_heartbeat_at')->nullable();
                $table->json('health_payload')->nullable();
                $table->string('status')->default('registered');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'warning_station_id'], 'wsd_project_station_idx');
                $table->index(['device_type', 'expected'], 'wsd_type_expected_idx');
                $table->index(['availability_state', 'health_state'], 'wsd_availability_health_idx');
            });
        }

        if (! Schema::hasTable('warning_station_device_heartbeats')) {
            Schema::create('warning_station_device_heartbeats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
                $table->foreignId('warning_station_id')->constrained('warning_stations')->cascadeOnDelete();
                $table->foreignId('warning_station_device_id')->nullable();
                $table->foreign('warning_station_device_id', 'wsdh_device_fk')->references('id')->on('warning_station_devices')->nullOnDelete();
                $table->string('device_code');
                $table->string('device_type')->nullable();
                $table->string('availability_state')->default('available');
                $table->string('health_state')->default('ok');
                $table->timestamp('observed_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->json('health_payload')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'warning_station_id'], 'wsdh_project_station_idx');
                $table->index(['device_code', 'received_at'], 'wsdh_device_received_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warning_station_device_heartbeats');
        Schema::dropIfExists('warning_station_devices');
        Schema::dropIfExists('warning_station_telemetry_configs');

        Schema::table('warning_stations', function (Blueprint $table) {
            $table->dropIndex('warning_project_registration_idx');
            $table->dropIndex('warning_project_status_idx');
            $table->dropConstrainedForeignId('registered_by_user_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn([
                'administrative_location',
                'notes',
                'registration_status',
                'registered_at',
            ]);
        });
    }
};
