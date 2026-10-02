<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seragamkan serial port RedNode ke /dev/ttyS9 (PIN 5-6 pada BL371-bliiot).
 *
 * Latar belakang: config lama memakai /dev/ttyAS2 (penamaan port model BL118)
 * yang TIDAK ada di hardware BL371. Akibatnya gateway gagal membuka serial
 * ("No such file or directory, cannot open /dev/ttyAS2"). Port yang benar
 * untuk unit ini adalah /dev/ttyS9.
 *
 * Migration menyasar nilai lama (pola /dev/ttyAS*) sehingga idempotent dan
 * aman di environment mana pun: jika sudah /dev/ttyS9, tidak ada yang berubah.
 */
return new class extends Migration
{
    private string $newPort = '/dev/ttyS9';
    private string $newPinPath = 'Pin 5-6 / /dev/ttyS9';
    private string $newPinMapping = 'Pin 5 = B, Pin 6 = A';

    public function up(): void
    {
        if (! Schema::hasTable('connectivity_configs')) {
            return;
        }

        $rows = DB::table('connectivity_configs')
            ->where(function ($q) {
                $q->where('serial_port', 'like', '%ttyAS%')
                    ->orWhere('host_or_endpoint', 'like', '%ttyAS%')
                    ->orWhere('topic_or_api_path', 'like', '%ttyAS%');
            })
            ->get();

        foreach ($rows as $row) {
            $update = [];

            if (! empty($row->serial_port) && str_contains($row->serial_port, 'ttyAS')) {
                $update['serial_port'] = $this->newPort;
            }
            if (! empty($row->host_or_endpoint) && str_contains($row->host_or_endpoint, 'ttyAS')) {
                $update['host_or_endpoint'] = $this->newPort;
            }
            if (! empty($row->topic_or_api_path) && str_contains($row->topic_or_api_path, 'ttyAS')) {
                $update['topic_or_api_path'] = $this->newPinPath;
            }

            // serial_settings JSON bisa menyimpan serial_port juga
            if (Schema::hasColumn('connectivity_configs', 'serial_settings') && ! empty($row->serial_settings)) {
                $settings = json_decode($row->serial_settings, true);
                if (is_array($settings) && isset($settings['serial_port']) && str_contains((string) $settings['serial_port'], 'ttyAS')) {
                    $settings['serial_port'] = $this->newPort;
                    $update['serial_settings'] = json_encode($settings);
                }
            }

            if (Schema::hasColumn('connectivity_configs', 'pin_mapping')) {
                $update['pin_mapping'] = $this->newPinMapping;
            }

            if (! empty($update)) {
                $update['updated_at'] = now();
                DB::table('connectivity_configs')->where('id', $row->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        // Tidak dibalik: port lama (/dev/ttyAS2) adalah nilai yang salah untuk
        // hardware ini, sehingga rollback ke nilai salah tidak diinginkan.
        // down() sengaja dibuat no-op yang aman.
    }
};
