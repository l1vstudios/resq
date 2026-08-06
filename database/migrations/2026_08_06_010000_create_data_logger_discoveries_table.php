<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_logger_discoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matched_data_logger_id')->nullable()->constrained('data_loggers')->nullOnDelete();
            $table->string('device_uid')->nullable()->index();
            $table->string('logger_code')->nullable()->index();
            $table->string('serial_number')->nullable()->index();
            $table->string('logger_model')->nullable();
            $table->string('vendor')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('device_label')->nullable();
            $table->string('hostname')->nullable();
            $table->string('request_ip')->nullable()->index();
            $table->json('mac_addresses')->nullable();
            $table->json('last_payload')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('status')->default('Detected');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_logger_discoveries');
    }
};
