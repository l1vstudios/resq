<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->unique(['mst_prefix_id', 'slave_id', 'address'], 'sensors_prefix_slave_address_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropUnique('sensors_prefix_slave_address_unique');
        });
    }
};
