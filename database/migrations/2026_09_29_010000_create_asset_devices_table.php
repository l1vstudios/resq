<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('resq_projects')->cascadeOnDelete();
            $table->foreignId('monitoring_station_id')->nullable()->constrained('monitoring_stations')->nullOnDelete();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->string('asset_type')->default('mppt_charge_controller');
            $table->string('vendor')->nullable();
            $table->string('model')->nullable();
            $table->string('protocol')->default('modbus_rtu');
            $table->string('serial_port')->nullable();
            $table->unsignedSmallInteger('slave_address')->default(1);
            $table->unsignedInteger('baud_rate')->default(115200);
            $table->unsignedTinyInteger('data_bits')->default(8);
            $table->string('parity', 8)->default('none');
            $table->unsignedTinyInteger('stop_bits')->default(1);
            $table->unsignedInteger('timeout_ms')->default(1000);
            $table->string('status')->default('Active');
            $table->timestamp('last_scanned_at')->nullable();
            $table->string('last_scan_status')->nullable();
            $table->text('last_scan_message')->nullable();
            $table->json('last_scan_payload')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['monitoring_station_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_devices');
    }
};
