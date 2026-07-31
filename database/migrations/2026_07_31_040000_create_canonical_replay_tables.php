<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canonical_replay_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_key', 64)->unique();
            $table->string('scope_key', 120);
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->timestamp('observed_from');
            $table->timestamp('observed_to');
            $table->foreignId('mapping_profile_version_id')->constrained('mapping_profile_versions')->restrictOnDelete();
            $table->string('reason', 1000);
            $table->string('status', 24)->default('draft')->index();
            $table->unsignedInteger('max_events')->default(10000);
            $table->foreignId('cursor_raw_event_id')->nullable()->constrained('raw_ingestion_events')->restrictOnDelete();
            $table->unsignedInteger('selected_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('corrected_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->json('dry_run_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dry_run_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['source_type', 'source_id', 'observed_from', 'observed_to'], 'canonical_replay_scope_range_index');
        });

        Schema::create('canonical_replay_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canonical_replay_batch_id')->constrained('canonical_replay_batches')->restrictOnDelete();
            $table->foreignId('raw_ingestion_event_id')->constrained('raw_ingestion_events')->restrictOnDelete();
            $table->foreignId('canonical_processing_run_id')->nullable()->constrained('canonical_processing_runs')->restrictOnDelete();
            $table->string('status', 24)->index();
            $table->unsignedInteger('previous_value_count')->default(0);
            $table->unsignedInteger('new_value_count')->default(0);
            $table->string('reason', 1000)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['canonical_replay_batch_id', 'raw_ingestion_event_id'], 'canonical_replay_item_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_replay_items');
        Schema::dropIfExists('canonical_replay_batches');
    }
};
