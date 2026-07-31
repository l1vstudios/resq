<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingress_rollout_states', function (Blueprint $table) {
            $table->id();
            $table->string('path_key', 64)->unique();
            $table->string('state', 24)->default('expand');
            $table->string('reason', 500);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('state_changed_at', 6);
            $table->timestamps(6);
        });

        Schema::create('ingress_rollout_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingress_rollout_state_id')
                ->constrained('ingress_rollout_states')
                ->restrictOnDelete();
            $table->string('path_key', 64);
            $table->string('from_state', 24);
            $table->string('to_state', 24);
            $table->string('reason', 500);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at', 6);
            $table->index(
                ['path_key', 'created_at'],
                'ingress_rollout_transition_path_time_index'
            );
        });

        Schema::create('ingress_rollout_evidence', function (Blueprint $table) {
            $table->id();
            $table->string('path_key', 64);
            $table->string('rollout_state', 24);
            $table->string('capture_outcome', 32);
            $table->string('reason_code', 100)->nullable();
            $table->string('payload_classification', 32)->nullable();
            $table->unsignedBigInteger('payload_size')->nullable();
            $table->char('payload_sha256', 64)->nullable();
            $table->foreignId('raw_ingestion_event_id')
                ->nullable()
                ->constrained('raw_ingestion_events')
                ->restrictOnDelete();
            $table->foreignId('canonical_processing_run_id')
                ->nullable()
                ->constrained('canonical_processing_runs')
                ->restrictOnDelete();
            $table->foreignId('mapping_profile_version_id')
                ->nullable()
                ->constrained('mapping_profile_versions')
                ->restrictOnDelete();
            $table->boolean('mapped')->nullable();
            $table->unsignedInteger('canonical_value_count')->default(0);
            $table->unsignedInteger('canonical_non_value_count')->default(0);
            $table->unsignedInteger('canonical_failure_count')->default(0);
            $table->boolean('compatibility_eligible')->default(false);
            $table->boolean('compatibility_projected')->default(false);
            $table->string('parity_status', 32)->nullable();
            $table->string('legacy_value_decimal', 120)->nullable();
            $table->string('canonical_value_decimal', 120)->nullable();
            $table->string('parity_difference_decimal', 120)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at', 6);
            $table->index(
                ['path_key', 'recorded_at'],
                'ingress_rollout_evidence_path_time_index'
            );
            $table->index(
                ['path_key', 'capture_outcome', 'recorded_at'],
                'ingress_rollout_evidence_outcome_time_index'
            );
        });

        Schema::create('ingress_verification_attestations', function (Blueprint $table) {
            $table->id();
            $table->string('path_key', 64);
            $table->string('suite_version', 80);
            $table->unsignedInteger('passed_count');
            $table->unsignedInteger('failed_count');
            $table->char('result_digest', 64);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at', 6);
            $table->index(
                ['path_key', 'suite_version', 'verified_at'],
                'ingress_verification_path_suite_time_index'
            );
        });

        $now = now();
        DB::table('ingress_rollout_states')->insert(array_map(
            fn (string $path): array => [
                'path_key' => $path,
                'state' => 'expand',
                'reason' => 'initial_expand',
                'actor_id' => null,
                'state_changed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            config('canonical.ingress_rollout.paths', [])
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('ingress_verification_attestations');
        Schema::dropIfExists('ingress_rollout_evidence');
        Schema::dropIfExists('ingress_rollout_transitions');
        Schema::dropIfExists('ingress_rollout_states');
    }
};
