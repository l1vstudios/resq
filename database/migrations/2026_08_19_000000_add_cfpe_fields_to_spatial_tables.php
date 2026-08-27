<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reference_points', function (Blueprint $table) {
            $table->decimal('chainage', 12, 2)->nullable()->after('notes');
            $table->string('segment_name')->nullable()->after('chainage');
            $table->string('corridor_code')->nullable()->after('segment_name');
            $table->decimal('distance_in_segment', 12, 2)->nullable()->after('corridor_code');
            $table->string('bm_id')->nullable()->after('distance_in_segment');
            $table->string('cfpe_id')->nullable()->after('bm_id');
        });

        Schema::table('reference_routes', function (Blueprint $table) {
            $table->decimal('total_length', 12, 2)->nullable()->after('notes');
            $table->string('corridor_code')->nullable()->after('total_length');
            $table->json('segment_data')->nullable()->after('corridor_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_points', function (Blueprint $table) {
            $table->dropColumn([
                'chainage',
                'segment_name',
                'corridor_code',
                'distance_in_segment',
                'bm_id',
                'cfpe_id',
            ]);
        });

        Schema::table('reference_routes', function (Blueprint $table) {
            $table->dropColumn([
                'total_length',
                'corridor_code',
                'segment_data',
            ]);
        });
    }
};
