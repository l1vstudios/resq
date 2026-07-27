<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_loggers', function (Blueprint $table) {
            if (! Schema::hasColumn('data_loggers', 'remote_host')) {
                $table->string('remote_host')->nullable()->after('device_label');
            }

            if (! Schema::hasColumn('data_loggers', 'remote_ssh_port')) {
                $table->unsignedInteger('remote_ssh_port')->nullable()->after('remote_host');
            }

            if (! Schema::hasColumn('data_loggers', 'remote_ssh_user')) {
                $table->string('remote_ssh_user')->nullable()->after('remote_ssh_port');
            }

            if (! Schema::hasColumn('data_loggers', 'remote_ssh_password')) {
                $table->text('remote_ssh_password')->nullable()->after('remote_ssh_user');
            }

            if (! Schema::hasColumn('data_loggers', 'remote_gateway_path')) {
                $table->string('remote_gateway_path')->nullable()->after('remote_ssh_password');
            }

            if (! Schema::hasColumn('data_loggers', 'remote_last_tested_at')) {
                $table->timestamp('remote_last_tested_at')->nullable()->after('remote_gateway_path');
            }

            if (! Schema::hasColumn('data_loggers', 'remote_last_status')) {
                $table->string('remote_last_status')->nullable()->after('remote_last_tested_at');
            }

            if (! Schema::hasColumn('data_loggers', 'remote_last_message')) {
                $table->text('remote_last_message')->nullable()->after('remote_last_status');
            }
        });

        Schema::table('sensors', function (Blueprint $table) {
            if (! Schema::hasColumn('sensors', 'weather_parameters')) {
                $table->json('weather_parameters')->nullable()->after('parameter');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            if (Schema::hasColumn('sensors', 'weather_parameters')) {
                $table->dropColumn('weather_parameters');
            }
        });

        Schema::table('data_loggers', function (Blueprint $table) {
            $columns = [
                'remote_host',
                'remote_ssh_port',
                'remote_ssh_user',
                'remote_ssh_password',
                'remote_gateway_path',
                'remote_last_tested_at',
                'remote_last_status',
                'remote_last_message',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('data_loggers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
