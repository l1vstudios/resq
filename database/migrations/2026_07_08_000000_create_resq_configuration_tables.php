<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resq_projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique();
            $table->string('name');
            $table->string('owner')->nullable();
            $table->date('project_date')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        Schema::create('geospatial_workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->string('workspace_code')->unique();
            $table->string('name');
            $table->string('hazard')->nullable();
            $table->string('province');
            $table->string('city')->nullable();
            $table->unsignedInteger('beneficiaries')->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('Normal');
            $table->timestamps();
        });

        Schema::create('monitoring_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('geospatial_workspaces')->cascadeOnDelete();
            $table->string('station_code')->unique();
            $table->string('name');
            $table->string('coordinate')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('logger_id')->nullable();
            $table->string('logger_status')->default('Active');
            $table->string('connectivity_status')->default('Online');
            $table->string('status')->default('Normal');
            $table->timestamps();
        });

        Schema::create('warning_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('geospatial_workspaces')->cascadeOnDelete();
            $table->foreignId('monitoring_station_id')->nullable()->constrained('monitoring_stations')->nullOnDelete();
            $table->string('station_code')->unique();
            $table->string('name');
            $table->string('zone_id')->nullable();
            $table->string('coordinate')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('controller_id')->nullable();
            $table->string('controller_model')->nullable();
            $table->string('controller_vendor')->nullable();
            $table->string('controller_status')->default('Standby');
            $table->json('output_devices')->nullable();
            $table->string('status')->default('Normal');
            $table->boolean('public_warning_enabled')->default(false);
            $table->string('ack_response')->nullable();
            $table->timestamps();
        });

        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('geospatial_workspaces')->cascadeOnDelete();
            $table->foreignId('monitoring_station_id')->constrained('monitoring_stations')->cascadeOnDelete();
            $table->foreignId('warning_station_id')->nullable()->constrained('warning_stations')->nullOnDelete();
            $table->string('sensor_code')->unique();
            $table->string('type');
            $table->string('parameter')->nullable();
            $table->string('value')->nullable();
            $table->string('threshold')->nullable();
            $table->string('data_type')->nullable();
            $table->decimal('scale_factor', 12, 4)->default(1);
            $table->decimal('offset', 12, 4)->default(0);
            $table->string('unit')->nullable();
            $table->string('reading_method')->default('Absolute');
            $table->string('alert_level')->default('Normal');
            $table->string('rule')->nullable();
            $table->string('status')->default('Normal');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('response_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained('geospatial_workspaces')->nullOnDelete();
            $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            $table->foreignId('warning_station_id')->nullable()->constrained('warning_stations')->nullOnDelete();
            $table->boolean('dashboard_notif')->default(true);
            $table->boolean('sms_blasting')->default(false);
            $table->boolean('warning_station_act')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_plans');
        Schema::dropIfExists('sensors');
        Schema::dropIfExists('warning_stations');
        Schema::dropIfExists('monitoring_stations');
        Schema::dropIfExists('geospatial_workspaces');
        Schema::dropIfExists('resq_projects');
    }
};
