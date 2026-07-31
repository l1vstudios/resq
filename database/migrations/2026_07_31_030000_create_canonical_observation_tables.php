<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canonical_processing_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_key', 64)->unique();
            $table->foreignId('raw_ingestion_event_id')->constrained('raw_ingestion_events')->restrictOnDelete();
            $table->foreignId('mapping_profile_version_id')->nullable()->constrained('mapping_profile_versions')->restrictOnDelete();
            $table->string('engine_version', 80);
            $table->string('run_mode', 24);
            $table->string('status', 32)->index();
            $table->string('reason', 1000)->nullable();
            $table->unsignedInteger('value_count')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('canonical_observations', function (Blueprint $table) {
            $table->id();
            $table->string('observation_key', 191)->unique();
            $table->foreignId('raw_ingestion_event_id')->unique()->constrained('raw_ingestion_events')->restrictOnDelete();
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('project_id')->nullable()->constrained('resq_projects')->nullOnDelete();
            $table->foreignId('monitoring_station_id')->nullable()->constrained('monitoring_stations')->nullOnDelete();
            $table->foreignId('data_logger_id')->nullable()->constrained('data_loggers')->nullOnDelete();
            $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            $table->timestamp('observed_at');
            $table->timestamp('received_at');
            $table->timestamps();
            $table->index(['source_type', 'source_id', 'observed_at'], 'canonical_observation_source_time_index');
        });

        Schema::create('canonical_values', function (Blueprint $table) {
            $table->id();
            $table->string('processing_key', 64)->unique();
            $table->foreignId('canonical_observation_id')->constrained('canonical_observations')->restrictOnDelete();
            $table->foreignId('canonical_processing_run_id')->constrained('canonical_processing_runs')->restrictOnDelete();
            $table->foreignId('raw_ingestion_event_id')->constrained('raw_ingestion_events')->restrictOnDelete();
            $table->foreignId('raw_ingestion_item_id')->constrained('raw_ingestion_items')->restrictOnDelete();
            $table->foreignId('mapping_profile_version_id')->constrained('mapping_profile_versions')->restrictOnDelete();
            $table->foreignId('mapping_rule_id')->constrained('mapping_rules')->restrictOnDelete();
            $table->foreignId('canonical_parameter_id')->constrained('canonical_parameters')->restrictOnDelete();
            $table->foreignId('canonical_parameter_version_id')->constrained('canonical_parameter_versions')->restrictOnDelete();
            $table->foreignId('canonical_unit_id')->constrained('canonical_units')->restrictOnDelete();
            $table->string('domain', 32);
            $table->string('data_type', 32);
            $table->string('value_decimal', 120)->nullable();
            $table->text('value_text')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->string('status', 32)->index();
            $table->string('quality', 32)->default('unchecked');
            $table->string('reason', 1000)->nullable();
            $table->string('origin', 8);
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('supersedes_id')->nullable()->constrained('canonical_values')->restrictOnDelete();
            $table->timestamp('observed_at');
            $table->timestamp('received_at');
            $table->timestamp('processed_at');
            $table->json('stage_trace')->nullable();
            $table->string('engine_version', 80);
            $table->string('run_mode', 24);
            $table->timestamps();
            $table->index(['canonical_parameter_id', 'observed_at'], 'canonical_value_parameter_time_index');
        });

        Schema::create('canonical_current_heads', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('canonical_parameter_id')->constrained('canonical_parameters')->restrictOnDelete();
            $table->foreignId('canonical_value_id')->constrained('canonical_values')->restrictOnDelete();
            $table->timestamp('winner_observed_at');
            $table->unsignedInteger('winner_mapping_version');
            $table->unsignedInteger('winner_revision');
            $table->timestamps();
            $table->unique(['source_type', 'source_id', 'canonical_parameter_id'], 'canonical_current_head_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_current_heads');
        Schema::dropIfExists('canonical_values');
        Schema::dropIfExists('canonical_observations');
        Schema::dropIfExists('canonical_processing_runs');
    }
};
