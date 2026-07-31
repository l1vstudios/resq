<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapping_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('profile_key', 120)->unique();
            $table->string('name', 160);
            $table->string('manufacturer', 120);
            $table->string('device_model', 120);
            $table->text('description')->nullable();
            $table->string('lifecycle', 24)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mapping_profile_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mapping_profile_id')->constrained('mapping_profiles')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 24)->default('draft')->index();
            $table->string('change_reason', 500)->nullable();
            $table->json('validation_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['mapping_profile_id', 'version'], 'mapping_profile_version_unique');
        });

        Schema::create('mapping_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mapping_profile_version_id')->constrained('mapping_profile_versions')->restrictOnDelete();
            $table->unsignedInteger('sort_order');
            $table->string('source_parameter', 160);
            $table->string('source_item_key', 160)->nullable();
            $table->string('parser', 32);
            $table->unsignedInteger('byte_offset')->default(0);
            $table->unsignedInteger('byte_length');
            $table->unsignedInteger('register_start')->nullable();
            $table->unsignedInteger('register_count')->nullable();
            $table->string('signedness', 16)->default('not_applicable');
            $table->string('byte_order', 16)->default('big');
            $table->string('word_order', 16)->default('high_low');
            $table->string('scale', 80)->default('1');
            $table->string('offset', 80)->default('0');
            $table->foreignId('source_unit_id')->nullable()->constrained('canonical_units')->restrictOnDelete();
            $table->foreignId('canonical_parameter_id')->constrained('canonical_parameters')->restrictOnDelete();
            $table->foreignId('canonical_parameter_version_id')->constrained('canonical_parameter_versions')->restrictOnDelete();
            $table->json('missing_markers')->nullable();
            $table->string('origin', 8);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['mapping_profile_version_id', 'sort_order'], 'mapping_rule_order_unique');
            $table->index(['mapping_profile_version_id', 'source_parameter'], 'mapping_rule_source_index');
        });

        Schema::create('mapping_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key', 120)->unique();
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('project_id')->nullable()->constrained('resq_projects')->nullOnDelete();
            $table->foreignId('monitoring_station_id')->nullable()->constrained('monitoring_stations')->nullOnDelete();
            $table->foreignId('data_logger_id')->nullable()->constrained('data_loggers')->nullOnDelete();
            $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            $table->foreignId('active_version_id')->constrained('mapping_profile_versions')->restrictOnDelete();
            $table->unsignedInteger('lock_version')->default(1);
            $table->string('activation_reason', 500);
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at');
            $table->timestamps();
            $table->index(['source_type', 'source_id'], 'mapping_assignment_source_index');
        });

        Schema::create('mapping_activation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mapping_assignment_id')->constrained('mapping_assignments')->restrictOnDelete();
            $table->foreignId('from_version_id')->nullable()->constrained('mapping_profile_versions')->restrictOnDelete();
            $table->foreignId('to_version_id')->constrained('mapping_profile_versions')->restrictOnDelete();
            $table->string('action', 24);
            $table->string('reason', 500);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
            $table->index(['mapping_assignment_id', 'created_at'], 'mapping_activation_history_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapping_activation_logs');
        Schema::dropIfExists('mapping_assignments');
        Schema::dropIfExists('mapping_rules');
        Schema::dropIfExists('mapping_profile_versions');
        Schema::dropIfExists('mapping_profiles');
    }
};
