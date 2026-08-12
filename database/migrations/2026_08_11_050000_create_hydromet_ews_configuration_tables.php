<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hydromet_ews_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('corridor_id')->constrained('corridor_monitorings')->cascadeOnDelete();
            $table->foreignId('monitoring_station_id')->constrained('monitoring_stations')->cascadeOnDelete();
            $table->foreignId('warning_station_id')->nullable()->constrained('warning_stations')->nullOnDelete();
            $table->string('relationship_code')->unique();
            $table->string('name');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'corridor_id', 'monitoring_station_id', 'warning_station_id'], 'hydromet_ews_relationship_scope_unique');
            $table->index(['project_id', 'status'], 'hews_project_status_idx');
        });

        Schema::create('hydromet_hazard_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('hydromet_ews_relationship_id');
            $table->foreign('hydromet_ews_relationship_id', 'hhc_relationship_fk')->references('id')->on('hydromet_ews_relationships')->cascadeOnDelete();
            $table->foreignId('corridor_id')->constrained('corridor_monitorings')->cascadeOnDelete();
            $table->foreignId('monitoring_station_id')->constrained('monitoring_stations')->cascadeOnDelete();
            $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            $table->foreignId('canonical_parameter_id')->nullable()->constrained('canonical_parameters')->nullOnDelete();
            $table->string('classification_code')->unique();
            $table->string('parameter');
            $table->string('reading_method');
            $table->json('threshold_config')->nullable();
            $table->json('hazard_levels');
            $table->json('unresolved_business_rules')->nullable();
            $table->string('evaluation_engine')->default('configuration_only');
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['project_id', 'monitoring_station_id'], 'hhc_project_station_idx');
            $table->index(['reading_method', 'status'], 'hhc_method_status_idx');
        });

        Schema::create('hydromet_wdam_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('hydromet_ews_relationship_id');
            $table->foreign('hydromet_ews_relationship_id', 'hwdam_relationship_fk')->references('id')->on('hydromet_ews_relationships')->cascadeOnDelete();
            $table->foreignId('warning_station_id')->nullable()->constrained('warning_stations')->nullOnDelete();
            $table->string('wdam_code')->unique();
            $table->boolean('dashboard_notification_enabled')->default(true);
            $table->json('registered_recipients')->nullable();
            $table->boolean('sms_enabled')->default(false);
            $table->string('sms_provider_ref')->nullable();
            $table->boolean('whatsapp_enabled')->default(false);
            $table->string('whatsapp_provider_ref')->nullable();
            $table->boolean('warning_station_assignment_enabled')->default(false);
            $table->boolean('automatic_activation_enabled')->default(false);
            $table->string('authority_method')->default('manual_authority');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status'], 'hwdam_project_status_idx');
            $table->index(['warning_station_id', 'warning_station_assignment_enabled'], 'hydromet_wdam_warning_station_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hydromet_wdam_configs');
        Schema::dropIfExists('hydromet_hazard_classifications');
        Schema::dropIfExists('hydromet_ews_relationships');
    }
};
