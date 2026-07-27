<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connectivity_configs', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('connectivity_status');
            $table->text('last_error')->nullable()->after('last_seen_at');
            $table->json('last_payload')->nullable()->after('last_error');
        });
    }

    public function down(): void
    {
        Schema::table('connectivity_configs', function (Blueprint $table) {
            $table->dropColumn(['last_seen_at', 'last_error', 'last_payload']);
        });
    }
};
