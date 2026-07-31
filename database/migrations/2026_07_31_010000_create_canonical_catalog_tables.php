<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canonical_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('symbol', 32);
            $table->string('name', 120);
            $table->string('dimension_key', 64)->index();
            $table->text('definition')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('canonical_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_unit_id')->constrained('canonical_units')->restrictOnDelete();
            $table->foreignId('target_unit_id')->constrained('canonical_units')->restrictOnDelete();
            $table->string('multiplier', 80);
            $table->string('offset', 80)->default('0');
            $table->boolean('is_approved')->default(true)->index();
            $table->string('approval_reference', 160)->nullable();
            $table->timestamps();
            $table->unique(['source_unit_id', 'target_unit_id'], 'canonical_unit_conversion_pair_unique');
        });

        Schema::create('canonical_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('key', 96)->unique();
            $table->string('domain', 32)->index();
            $table->string('lifecycle', 24)->default('active')->index();
            $table->unsignedInteger('current_version')->default(1);
            $table->timestamp('deprecated_at')->nullable();
            $table->foreignId('deprecated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('deprecation_reason', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('canonical_parameter_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canonical_parameter_id')->constrained('canonical_parameters')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('display_name', 160);
            $table->text('definition');
            $table->foreignId('canonical_unit_id')->constrained('canonical_units')->restrictOnDelete();
            $table->string('data_type', 32);
            $table->string('measurement_characteristic', 64);
            $table->unsignedTinyInteger('output_precision')->default(2);
            $table->string('rounding_mode', 32)->default('half_up');
            $table->string('source_document', 160);
            $table->string('source_reference', 160)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('effective_at');
            $table->timestamp('retired_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['canonical_parameter_id', 'version'], 'canonical_parameter_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_parameter_versions');
        Schema::dropIfExists('canonical_parameters');
        Schema::dropIfExists('canonical_unit_conversions');
        Schema::dropIfExists('canonical_units');
    }
};
