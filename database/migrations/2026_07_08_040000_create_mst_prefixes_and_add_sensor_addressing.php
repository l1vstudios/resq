<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_prefixes', function (Blueprint $table) {
            $table->id();
            $table->string('prefix_code')->unique();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        Schema::table('sensors', function (Blueprint $table) {
            $table->foreignId('mst_prefix_id')->nullable()->after('warning_station_id')->constrained('mst_prefixes')->nullOnDelete();
            $table->string('slave_id')->nullable()->after('mst_prefix_id');
            $table->string('address')->nullable()->after('slave_id');
        });
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mst_prefix_id');
            $table->dropColumn(['slave_id', 'address']);
        });

        Schema::dropIfExists('mst_prefixes');
    }
};
