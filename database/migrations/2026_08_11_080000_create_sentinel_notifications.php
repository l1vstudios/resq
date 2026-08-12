<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sentinel_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('corridor_id')->nullable()->constrained('corridor_monitorings')->nullOnDelete();
            $table->foreignId('monitoring_station_id')->nullable()->constrained('monitoring_stations')->nullOnDelete();
            $table->foreignId('warning_station_id')->nullable()->constrained('warning_stations')->nullOnDelete();
            $table->string('category');
            $table->string('event_type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('source_context')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'occurred_at'], 'notifications_user_read_idx');
            $table->index(['client_id', 'project_id', 'occurred_at'], 'notifications_client_project_idx');
            $table->index(['category', 'event_type'], 'notifications_category_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentinel_notifications');
    }
};
