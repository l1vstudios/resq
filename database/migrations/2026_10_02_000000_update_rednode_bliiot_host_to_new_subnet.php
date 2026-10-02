<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pindah alamat RedNode Bliiot dari subnet lama 192.168.1.110
 * ke subnet baru 192.168.3.110.
 *
 * Latar belakang: kabel USB LAN dari server ke RedNode semula memakai
 * subnet 192.168.1.0/24 yang bentrok dengan WiFi, sehingga akses hanya
 * bisa saat WiFi dimatikan. RedNode dipindah ke 192.168.3.0/24 (subnet
 * terpisah) supaya WiFi dan akses kabel bisa hidup bersamaan.
 *
 * Migration ini menyasar berdasarkan NILAI IP lama (bukan id), sehingga
 * idempotent dan aman dijalankan di environment mana pun: jika tidak ada
 * baris dengan IP lama, tidak ada yang diubah.
 */
return new class extends Migration
{
    private string $oldHost = '192.168.1.110';
    private string $newHost = '192.168.3.110';

    public function up(): void
    {
        // Alamat remote SSH logger (tab Data Loggers)
        if (Schema::hasTable('data_loggers') && Schema::hasColumn('data_loggers', 'remote_host')) {
            DB::table('data_loggers')
                ->where('remote_host', $this->oldHost)
                ->update([
                    'remote_host' => $this->newHost,
                    'updated_at' => now(),
                ]);
        }

        // Alamat RedNode pada Connectivity (tab RedNode) bila dipakai
        if (Schema::hasTable('connectivity_configs') && Schema::hasColumn('connectivity_configs', 'rednode_host')) {
            DB::table('connectivity_configs')
                ->where('rednode_host', $this->oldHost)
                ->update([
                    'rednode_host' => $this->newHost,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('data_loggers') && Schema::hasColumn('data_loggers', 'remote_host')) {
            DB::table('data_loggers')
                ->where('remote_host', $this->newHost)
                ->update([
                    'remote_host' => $this->oldHost,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('connectivity_configs') && Schema::hasColumn('connectivity_configs', 'rednode_host')) {
            DB::table('connectivity_configs')
                ->where('rednode_host', $this->newHost)
                ->update([
                    'rednode_host' => $this->oldHost,
                    'updated_at' => now(),
                ]);
        }
    }
};
