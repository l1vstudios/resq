<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->string('function_code')->default('FC03')->after('address');
            $table->unsignedInteger('quantity')->default(1)->after('function_code');
            $table->unsignedInteger('poll_interval_ms')->default(1000)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropColumn(['function_code', 'quantity', 'poll_interval_ms']);
        });
    }
};
