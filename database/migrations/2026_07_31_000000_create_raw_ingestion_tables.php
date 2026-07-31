<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_ingestion_events', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->string('logical_event_key', 191);
            $table->foreignId('project_id')->nullable()->constrained('resq_projects')->nullOnDelete();
            $table->foreignId('monitoring_station_id')->nullable()->constrained('monitoring_stations')->nullOnDelete();
            $table->foreignId('data_logger_id')->nullable()->constrained('data_loggers')->nullOnDelete();
            $table->foreignId('sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            $table->string('transport', 32);
            $table->string('envelope_version', 20)->default('1');
            $table->string('payload_classification', 32);
            $table->string('content_type', 127)->nullable();
            $table->string('content_encoding', 32)->nullable();
            $table->binary('payload');
            $table->char('payload_hash', 64);
            $table->unsignedBigInteger('payload_size');
            $table->json('inspection_payload')->nullable();
            $table->json('source_snapshot');
            $table->timestamp('observed_at', 6)->nullable();
            $table->string('observed_at_provenance', 32)->default('device');
            $table->boolean('observed_at_fallback')->default(false);
            $table->timestamp('received_at', 6);
            $table->timestamp('processed_at', 6)->nullable();
            $table->string('receipt_status', 32)->default('accepted');
            $table->string('processing_status', 32)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps(6);

            $table->unique(
                ['source_type', 'source_id', 'logical_event_key'],
                'raw_events_source_event_unique'
            );
            $table->index(['source_type', 'source_id', 'received_at'], 'raw_events_source_received_idx');
            $table->index(['sensor_id', 'observed_at'], 'raw_events_sensor_observed_idx');
            $table->index(['receipt_status', 'processing_status'], 'raw_events_status_idx');
            $table->index('payload_hash', 'raw_events_payload_hash_idx');
        });

        Schema::create('raw_ingestion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_ingestion_event_id')
                ->constrained('raw_ingestion_events')
                ->restrictOnDelete();
            $table->string('item_key', 191);
            $table->string('source_parameter', 191)->nullable();
            $table->text('raw_value')->nullable();
            $table->binary('raw_bytes')->nullable();
            $table->char('raw_hash', 64)->nullable();
            $table->unsignedInteger('register_address')->nullable();
            $table->unsignedSmallInteger('register_count')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 32)->default('received');
            $table->text('reason')->nullable();
            $table->timestamps(6);

            $table->unique(['raw_ingestion_event_id', 'item_key'], 'raw_items_event_key_unique');
            $table->index(['source_parameter', 'created_at'], 'raw_items_parameter_created_idx');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE raw_ingestion_events MODIFY payload LONGBLOB NOT NULL');
            DB::statement('ALTER TABLE raw_ingestion_items MODIFY raw_bytes MEDIUMBLOB NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_ingestion_items');
        Schema::dropIfExists('raw_ingestion_events');
    }
};
