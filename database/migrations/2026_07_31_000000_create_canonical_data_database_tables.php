<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_data_ingestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitoring_station_id')->nullable()->constrained('monitoring_stations')->nullOnDelete();
            $table->foreignId('data_logger_id')->nullable()->constrained('data_loggers')->nullOnDelete();
            $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            $table->string('source_device_identity')->nullable();
            $table->string('source_parameter')->nullable();
            $table->string('register_address')->nullable();
            $table->string('function_code')->nullable();
            $table->string('data_type')->nullable();
            $table->unsignedInteger('data_length')->nullable();
            $table->string('byte_order')->nullable();
            $table->decimal('scale_factor', 18, 8)->default(1);
            $table->decimal('offset', 18, 8)->default(0);
            $table->string('source_unit')->nullable();
            $table->string('raw_value')->nullable();
            $table->json('payload')->nullable();
            $table->enum('raw_data_classification', ['direct_measurement', 'device_processed'])->default('direct_measurement');
            $table->timestamp('observed_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('reception_status')->default('received');
            $table->timestamps();

            $table->index(['monitoring_station_id', 'observed_at']);
            $table->index(['sensor_id', 'source_parameter']);
        });

        Schema::create('canonical_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('field_identity')->unique();
            $table->text('definition')->nullable();
            $table->enum('domain', ['meteorology', 'hydrology', 'geotechnical']);
            $table->string('canonical_unit')->nullable();
            $table->string('data_type')->default('decimal');
            $table->string('measurement_characteristic')->nullable();
            $table->boolean('is_platform_processed')->default(false);
            $table->json('source_fields')->nullable();
            $table->text('formula')->nullable();
            $table->json('input_requirements')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['domain', 'status']);
        });

        Schema::create('sensor_mapping_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            $table->string('profile_code')->unique();
            $table->string('manufacturer')->nullable();
            $table->string('device_model')->nullable();
            $table->string('communication_path')->nullable();
            $table->unsignedInteger('slave_id')->nullable();
            $table->string('source_parameter');
            $table->string('source_unit')->nullable();
            $table->string('register_address')->nullable();
            $table->string('function_code')->nullable();
            $table->string('value_type')->nullable();
            $table->unsignedInteger('data_length')->nullable();
            $table->string('byte_order')->nullable();
            $table->decimal('scale_factor', 18, 8)->default(1);
            $table->decimal('offset', 18, 8)->default(0);
            $table->text('value_interpretation')->nullable();
            $table->foreignId('canonical_parameter_id')->constrained('canonical_parameters')->cascadeOnDelete();
            $table->enum('value_origin', ['direct_measurement', 'device_processed'])->default('direct_measurement');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['sensor_id', 'source_parameter']);
        });

        Schema::create('canonical_observations', function (Blueprint $table) {
            $table->id();
            $table->uuid('canonical_observation_uid')->unique();
            $table->foreignId('monitoring_station_id')->nullable()->constrained('monitoring_stations')->nullOnDelete();
            $table->foreignId('data_logger_id')->nullable()->constrained('data_loggers')->nullOnDelete();
            $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            $table->enum('domain', ['meteorology', 'hydrology', 'geotechnical']);
            $table->timestamp('observed_at');
            $table->timestamp('received_at')->nullable();
            $table->json('field_values')->nullable();
            $table->json('field_units')->nullable();
            $table->json('field_origins')->nullable();
            $table->json('field_quality')->nullable();
            $table->json('processing_statuses')->nullable();
            $table->enum('quality_status', ['valid', 'suspect', 'invalid', 'limited', 'not_available'])->default('valid');
            $table->enum('completeness_status', ['complete', 'partial', 'missing_required'])->default('partial');
            $table->enum('processing_status', ['mapped', 'processed', 'late', 'calculation_failed', 'pending'])->default('mapped');
            $table->foreignId('raw_data_ingestion_id')->nullable()->constrained('raw_data_ingestions')->nullOnDelete();
            $table->foreignId('sensor_mapping_profile_id')->nullable()->constrained('sensor_mapping_profiles')->nullOnDelete();
            $table->json('traceability')->nullable();
            $table->timestamps();

            $table->unique(['monitoring_station_id', 'sensor_id', 'domain', 'observed_at'], 'canonical_observation_unique_scope');
            $table->index(['domain', 'observed_at']);
        });

        Schema::create('canonical_parameter_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canonical_observation_id')->constrained('canonical_observations')->cascadeOnDelete();
            $table->foreignId('canonical_parameter_id')->constrained('canonical_parameters')->cascadeOnDelete();
            $table->decimal('numeric_value', 24, 8)->nullable();
            $table->string('string_value')->nullable();
            $table->string('canonical_unit')->nullable();
            $table->enum('value_origin', ['reidentified_direct_measurement', 'reidentified_device_processed', 'platform_processed']);
            $table->enum('quality_status', ['valid', 'suspect', 'invalid', 'limited', 'not_available'])->default('valid');
            $table->foreignId('raw_data_ingestion_id')->nullable()->constrained('raw_data_ingestions')->nullOnDelete();
            $table->foreignId('sensor_mapping_profile_id')->nullable()->constrained('sensor_mapping_profiles')->nullOnDelete();
            $table->json('traceability')->nullable();
            $table->timestamps();

            $table->unique(['canonical_observation_id', 'canonical_parameter_id'], 'canonical_value_unique_parameter');
            $table->index(['canonical_parameter_id', 'value_origin'], 'idx_can_param_origin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_parameter_values');
        Schema::dropIfExists('canonical_observations');
        Schema::dropIfExists('sensor_mapping_profiles');
        Schema::dropIfExists('canonical_parameters');
        Schema::dropIfExists('raw_data_ingestions');
    }
};
