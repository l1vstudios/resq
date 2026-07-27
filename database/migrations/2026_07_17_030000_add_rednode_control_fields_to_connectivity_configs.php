<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connectivity_configs', function (Blueprint $table) {
            $table->string('rednode_host')->nullable()->after('monitored_sensor_ids');
            $table->unsignedInteger('rednode_ssh_port')->nullable()->after('rednode_host');
            $table->string('rednode_ssh_user')->nullable()->after('rednode_ssh_port');
            $table->text('rednode_ssh_password')->nullable()->after('rednode_ssh_user');
            $table->string('rednode_gateway_path')->nullable()->after('rednode_ssh_password');
            $table->unsignedInteger('rednode_poll_interval_ms')->nullable()->after('rednode_gateway_path');
        });
    }

    public function down(): void
    {
        Schema::table('connectivity_configs', function (Blueprint $table) {
            $table->dropColumn([
                'rednode_host',
                'rednode_ssh_port',
                'rednode_ssh_user',
                'rednode_ssh_password',
                'rednode_gateway_path',
                'rednode_poll_interval_ms',
            ]);
        });
    }
};
