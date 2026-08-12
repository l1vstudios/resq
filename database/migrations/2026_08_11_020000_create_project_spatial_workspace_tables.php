<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geospatial_workspaces', function (Blueprint $table) {
            $table->string('basemap_provider')->default('OpenStreetMap')->after('status');
            $table->string('basemap_tile_url')->nullable()->after('basemap_provider');
            $table->unsignedTinyInteger('default_zoom')->default(5)->after('basemap_tile_url');
            $table->json('map_bounds')->nullable()->after('default_zoom');
        });

        Schema::create('spatial_information_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained('geospatial_workspaces')->nullOnDelete();
            $table->string('layer_code')->unique();
            $table->string('name');
            $table->string('layer_type')->default('overlay');
            $table->string('source_url')->nullable();
            $table->json('layer_payload')->nullable();
            $table->string('style_color', 20)->nullable();
            $table->boolean('visible_by_default')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('Active');
            $table->timestamps();

            $table->index(['project_id', 'workspace_id']);
            $table->index(['project_id', 'status']);
        });

        Schema::create('reference_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained('geospatial_workspaces')->nullOnDelete();
            $table->string('route_code')->unique();
            $table->string('name');
            $table->string('route_type')->default('reference');
            $table->json('path_coordinates')->nullable();
            $table->string('status')->default('Active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'workspace_id']);
            $table->index(['project_id', 'status']);
        });

        Schema::create('corridor_monitorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('geospatial_workspaces')->cascadeOnDelete();
            $table->foreignId('reference_route_id')->nullable()->constrained('reference_routes')->nullOnDelete();
            $table->string('corridor_code')->unique();
            $table->string('name');
            $table->json('path_coordinates')->nullable();
            $table->string('status')->default('Planned');
            $table->json('status_metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'workspace_id']);
            $table->index(['project_id', 'status']);
        });

        Schema::create('reference_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained('geospatial_workspaces')->nullOnDelete();
            $table->foreignId('corridor_id')->nullable()->constrained('corridor_monitorings')->nullOnDelete();
            $table->foreignId('reference_route_id')->nullable()->constrained('reference_routes')->nullOnDelete();
            $table->string('point_code')->unique();
            $table->string('name');
            $table->string('point_type')->default('reference');
            $table->string('coordinate')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('Active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'workspace_id']);
            $table->index(['project_id', 'point_type']);
        });

        Schema::create('station_spatial_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('geospatial_workspaces')->cascadeOnDelete();
            $table->foreignId('corridor_id')->nullable()->constrained('corridor_monitorings')->nullOnDelete();
            $table->foreignId('reference_route_id')->nullable()->constrained('reference_routes')->nullOnDelete();
            $table->foreignId('reference_point_id')->nullable()->constrained('reference_points')->nullOnDelete();
            $table->foreignId('monitoring_station_id')->nullable()->constrained('monitoring_stations')->cascadeOnDelete();
            $table->foreignId('warning_station_id')->nullable()->constrained('warning_stations')->cascadeOnDelete();
            $table->string('placement_role')->default('corridor_reference');
            $table->string('station_offset')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();

            $table->index(['project_id', 'workspace_id']);
            $table->index(['corridor_id', 'placement_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_spatial_references');
        Schema::dropIfExists('reference_points');
        Schema::dropIfExists('corridor_monitorings');
        Schema::dropIfExists('reference_routes');
        Schema::dropIfExists('spatial_information_layers');

        Schema::table('geospatial_workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'basemap_provider',
                'basemap_tile_url',
                'default_zoom',
                'map_bounds',
            ]);
        });
    }
};
