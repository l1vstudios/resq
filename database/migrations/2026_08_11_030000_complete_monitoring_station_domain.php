<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_stations', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('workspace_id')->constrained('resq_projects')->nullOnDelete();
            $table->foreignId('corridor_id')->nullable()->after('project_id')->constrained('corridor_monitorings')->nullOnDelete();
            $table->string('station_type')->default('environmental_monitoring')->after('name');
            $table->string('registration_status')->default('registered')->after('connectivity_status');
            $table->timestamp('registered_at')->nullable()->after('registration_status');
            $table->foreignId('registered_by_user_id')->nullable()->after('registered_at')->constrained('users')->nullOnDelete();

            $table->index(['project_id', 'station_type']);
            $table->index(['project_id', 'registration_status']);
        });

        DB::table('monitoring_stations')
            ->orderBy('id')
            ->get()
            ->each(function ($station) {
                $projectId = DB::table('geospatial_workspaces')
                    ->where('id', $station->workspace_id)
                    ->value('project_id');

                DB::table('monitoring_stations')
                    ->where('id', $station->id)
                    ->update([
                        'project_id' => $projectId,
                        'registered_at' => $station->created_at ?? now(),
                    ]);
            });

        Schema::table('connectivity_configs', function (Blueprint $table) {
            $table->timestamp('last_connected_at')->nullable()->after('last_seen_at');
            $table->string('connection_state')->default('unknown')->after('connectivity_status');
            $table->string('uplink_state')->default('unknown')->after('connection_state');

            $table->index(['connection_state', 'uplink_state']);
        });

        DB::table('connectivity_configs')->update([
            'connection_state' => DB::raw("LOWER(COALESCE(connectivity_status, 'unknown'))"),
            'uplink_state' => DB::raw("LOWER(COALESCE(connectivity_status, 'unknown'))"),
            'last_connected_at' => DB::raw('last_seen_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('connectivity_configs', function (Blueprint $table) {
            $table->dropIndex(['connection_state', 'uplink_state']);
            $table->dropColumn(['last_connected_at', 'connection_state', 'uplink_state']);
        });

        Schema::table('monitoring_stations', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'station_type']);
            $table->dropIndex(['project_id', 'registration_status']);
            $table->dropConstrainedForeignId('registered_by_user_id');
            $table->dropConstrainedForeignId('corridor_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['station_type', 'registration_status', 'registered_at']);
        });
    }
};
