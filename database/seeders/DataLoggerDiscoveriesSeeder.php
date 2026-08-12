<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataLoggerDiscoveriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => 1,
                'matched_data_logger_id' => 5,
                'device_uid' => 'rn-b16752f7e8cf1c81',
                'logger_code' => 'REDNODE-BLIIOT-011',
                'serial_number' => null,
                'logger_model' => 'RedNode Bliiot',
                'vendor' => 'Bliiot',
                'firmware_version' => 'Ubuntu 20.04.5 LTS',
                'device_label' => 'BL118-bliiot',
                'hostname' => 'BL118-bliiot',
                'request_ip' => '192.168.3.1',
                'mac_addresses' => '["00:e0:9a:24:8a:d0"]',
                'last_payload' => '{"vendor": "Bliiot", "hostname": "BL118-bliiot", "platform": "linux arm 5.4.61", "device_uid": "rn-b16752f7e8cf1c81", "logger_code": "REDNODE-BLIIOT-011", "device_label": "BL118-bliiot", "logger_model": "RedNode Bliiot", "mac_addresses": ["00:e0:9a:24:8a:d0"], "gateway_version": "1.0.0", "firmware_version": "Ubuntu 20.04.5 LTS"}',
                'last_seen_at' => '2026-08-11 12:12:07',
                'status' => 'Matched',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:12:07'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('data_logger_discoveries')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}