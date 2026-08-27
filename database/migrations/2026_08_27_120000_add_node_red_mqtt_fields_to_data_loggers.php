<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_loggers', function (Blueprint $table) {
            if (! Schema::hasColumn('data_loggers', 'node_red_mqtt_configuration_id')) {
                $table->foreignId('node_red_mqtt_configuration_id')
                    ->nullable()
                    ->after('remote_last_message')
                    ->constrained('mqtt_configurations')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('data_loggers', 'node_red_publish_topic')) {
                $table->string('node_red_publish_topic')->nullable()->after('node_red_mqtt_configuration_id');
            }

            if (! Schema::hasColumn('data_loggers', 'node_red_service_name')) {
                $table->string('node_red_service_name')->nullable()->after('node_red_publish_topic');
            }

            if (! Schema::hasColumn('data_loggers', 'node_red_user_dir')) {
                $table->string('node_red_user_dir')->nullable()->after('node_red_service_name');
            }

            if (! Schema::hasColumn('data_loggers', 'node_red_environment_file')) {
                $table->string('node_red_environment_file')->nullable()->after('node_red_user_dir');
            }

            if (! Schema::hasColumn('data_loggers', 'node_red_restart_command')) {
                $table->string('node_red_restart_command')->nullable()->after('node_red_environment_file');
            }

            if (! Schema::hasColumn('data_loggers', 'node_red_last_applied_at')) {
                $table->timestamp('node_red_last_applied_at')->nullable()->after('node_red_restart_command');
            }

            if (! Schema::hasColumn('data_loggers', 'node_red_last_tested_at')) {
                $table->timestamp('node_red_last_tested_at')->nullable()->after('node_red_last_applied_at');
            }

            if (! Schema::hasColumn('data_loggers', 'node_red_last_status')) {
                $table->string('node_red_last_status')->nullable()->after('node_red_last_tested_at');
            }

            if (! Schema::hasColumn('data_loggers', 'node_red_last_message')) {
                $table->text('node_red_last_message')->nullable()->after('node_red_last_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_loggers', function (Blueprint $table) {
            if (Schema::hasColumn('data_loggers', 'node_red_mqtt_configuration_id')) {
                $table->dropConstrainedForeignId('node_red_mqtt_configuration_id');
            }

            foreach ([
                'node_red_publish_topic',
                'node_red_service_name',
                'node_red_user_dir',
                'node_red_environment_file',
                'node_red_restart_command',
                'node_red_last_applied_at',
                'node_red_last_tested_at',
                'node_red_last_status',
                'node_red_last_message',
            ] as $column) {
                if (Schema::hasColumn('data_loggers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
