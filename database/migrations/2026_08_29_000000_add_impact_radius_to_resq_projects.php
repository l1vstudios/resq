<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resq_projects', function (Blueprint $table) {
            $table->decimal('impact_radius_km', 8, 2)->default(25)->after('project_date');
        });
    }

    public function down(): void
    {
        Schema::table('resq_projects', function (Blueprint $table) {
            $table->dropColumn('impact_radius_km');
        });
    }
};
