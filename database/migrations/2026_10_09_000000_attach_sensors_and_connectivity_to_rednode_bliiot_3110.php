<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kaitkan sensor RIKA-CUACA & SENSOR-LEMBAB ke Data Logger RedNode Bliiot
 * yang benar-benar terkoneksi (REDNODE-BLIIOT-3.110 @ 192.168.3.110), lalu
 * pastikan ada baris ConnectivityConfig supaya logger terbaca "Online" di
 * Project Live Monitoring.
 *
 * Latar belakang: device fisik RedNode (hostname BL371-bliiot, UID
 * rn-c23e58c1e84cb699) sekarang diakses lewat IP tambahan 192.168.3.110.
 * Sensor sempat tertempel ke logger lain sehingga hitungan "Parameter
 * Realtime" selalu 0 walau telemetry masuk, karena logika monitoring
 * menandai sensor fresh hanya bila logger pemiliknya online.
 *
 * Migration ini:
 *   1. Memindahkan sensor RIKA-CUACA & SENSOR-LEMBAB ke logger 3.110.
 *   2. Merapikan device_label logger 3.110 agar sesuai hostname device.
 *   3. Membuat / memperbarui ConnectivityConfig logger 3.110 (serial Modbus
 *      RTU /dev/ttyS9, host 192.168.3.110) secara idempotent.
 *
 * Catatan: logger REDNODE-BLIIOT-50.110 TIDAK disentuh sama sekali.
 *
 * Target berdasarkan NILAI (logger_code / sensor_code), bukan id, sehingga
 * idempotent dan aman di MySQL maupun PostgreSQL dan di environment mana pun.
 */
return new class extends Migration
{
    private string $loggerCode = 'REDNODE-BLIIOT-3.110';
    private string $deviceLabel = 'BL371-bliiot';
    private string $rednodeHost = '192.168.3.110';
    private array $sensorCodes = ['RIKA-CUACA', 'SENSOR-LEMBAB'];
    private string $connectivityCode = 'SERIAL-REDNODE-BLIIOT-3.110';

    public function up(): void
    {
        if (! Schema::hasTable('data_loggers')) {
            return;
        }

        $logger = DB::table('data_loggers')
            ->where('logger_code', $this->loggerCode)
            ->first();

        // Kalau logger target belum ada, tidak melakukan apa-apa (aman).
        if (! $logger) {
            return;
        }

        // 1. Rapikan device_label logger 3.110 agar konsisten dengan hostname.
        if (Schema::hasColumn('data_loggers', 'device_label')) {
            DB::table('data_loggers')
                ->where('id', $logger->id)
                ->update([
                    'device_label' => $this->deviceLabel,
                    'updated_at' => now(),
                ]);
        }

        // 2. Tempelkan sensor ke logger 3.110 (hanya jika belum).
        if (Schema::hasTable('sensors') && Schema::hasColumn('sensors', 'data_logger_id')) {
            DB::table('sensors')
                ->whereIn('sensor_code', $this->sensorCodes)
                ->where(function ($q) use ($logger) {
                    $q->whereNull('data_logger_id')
                        ->orWhere('data_logger_id', '!=', $logger->id);
                })
                ->update([
                    'data_logger_id' => $logger->id,
                    'updated_at' => now(),
                ]);
        }

        // 3. Pastikan ada ConnectivityConfig untuk logger 3.110 (idempotent).
        if (Schema::hasTable('connectivity_configs')) {
            $attributes = ['connectivity_code' => $this->connectivityCode];

            $values = [
                'data_logger_id' => $logger->id,
                'connectivity_status' => 'Online',
                'connection_state' => 'connected',
                'uplink_state' => 'uplink',
                'last_seen_at' => now(),
                'last_connected_at' => now(),
                'updated_at' => now(),
            ];

            // Isi kolom opsional hanya bila tersedia di skema.
            $optional = [
                'communication_type' => 'Serial',
                'protocol' => 'Modbus RTU',
                'serial_port' => '/dev/ttyS9',
                'baud_rate' => 9600,
                'data_bits' => 8,
                'stop_bits' => 1,
                'parity' => 'none',
                'timeout_ms' => 1500,
                'rednode_host' => $this->rednodeHost,
                'rednode_ssh_port' => 22,
                'rednode_ssh_user' => 'root',
                'rednode_gateway_path' => '/root/rednode-gateway',
                'rednode_poll_interval_ms' => 1000,
            ];

            foreach ($optional as $column => $value) {
                if (Schema::hasColumn('connectivity_configs', $column)) {
                    $values[$column] = $value;
                }
            }

            $exists = DB::table('connectivity_configs')
                ->where('connectivity_code', $this->connectivityCode)
                ->exists();

            if (! $exists) {
                $values['created_at'] = now();
            }

            // updateOrInsert tidak menyentuh kolom id, sehingga sequence
            // PostgreSQL tetap aman (tidak perlu reset).
            DB::table('connectivity_configs')->updateOrInsert($attributes, $values);
        }
    }

    public function down(): void
    {
        // Hapus hanya ConnectivityConfig yang dibuat migration ini.
        if (Schema::hasTable('connectivity_configs')) {
            DB::table('connectivity_configs')
                ->where('connectivity_code', $this->connectivityCode)
                ->delete();
        }

        // device_label & relasi sensor sengaja TIDAK dibalik: nilai sebelumnya
        // adalah kondisi salah (label BL118 / sensor tertempel ke logger keliru)
        // yang justru menyebabkan bug, sehingga rollback dibiarkan no-op aman.
    }
};
