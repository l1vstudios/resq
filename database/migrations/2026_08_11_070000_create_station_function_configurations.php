<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_function_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('monitoring_station_id')->constrained('monitoring_stations')->cascadeOnDelete();
            $table->string('function_name');
            $table->string('reading_method');
            $table->json('configuration')->nullable();
            $table->string('validation_state')->default('not_validated');
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->json('unresolved_analytical_rules')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['monitoring_station_id', 'function_name'], 'station_function_unique');
            $table->index(['project_id', 'function_name', 'status'], 'station_function_project_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_function_configurations');
    }
};
