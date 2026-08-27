<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mqtt_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->string('configuration_code')->unique();
            $table->string('name');
            $table->string('broker_url');
            $table->string('username')->nullable();
            $table->text('password_ciphertext')->nullable();
            $table->boolean('consumer_enabled')->default(false);
            $table->string('consumer_topic')->nullable();
            $table->unsignedTinyInteger('consumer_qos')->default(0);
            $table->json('example_payload')->nullable();
            $table->string('sensor_code_path')->nullable();
            $table->boolean('producer_enabled')->default(false);
            $table->string('producer_topic')->nullable();
            $table->unsignedTinyInteger('producer_qos')->default(0);
            $table->boolean('producer_retain')->default(false);
            $table->boolean('publish_canonical')->default(false);
            $table->boolean('publish_warning')->default(false);
            $table->json('canonical_parameter_ids')->nullable();
            $table->json('warning_levels')->nullable();
            $table->text('canonical_template')->nullable();
            $table->text('warning_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('connection_status')->default('inactive');
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_received_at')->nullable();
            $table->timestamp('last_published_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('runtime_metrics')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'is_active']);
        });

        Schema::table('sensors', function (Blueprint $table) {
            $table->string('input_source')->default('data_logger')->after('data_logger_id');
            $table->foreignId('mqtt_configuration_id')->nullable()->after('input_source')
                ->constrained('mqtt_configurations')->nullOnDelete();
        });

        Schema::create('mqtt_outbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mqtt_configuration_id')->constrained('mqtt_configurations')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('fingerprint')->unique();
            $table->string('topic');
            $table->unsignedTinyInteger('qos')->default(0);
            $table->boolean('retain')->default(false);
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mqtt_outbox_messages');
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mqtt_configuration_id');
            $table->dropColumn('input_source');
        });
        Schema::dropIfExists('mqtt_configurations');
    }
};
