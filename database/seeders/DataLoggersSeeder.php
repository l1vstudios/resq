<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataLoggersSeeder extends Seeder
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
                'id' => 5,
                'monitoring_station_id' => 4,
                'logger_code' => 'REDNODE-BLIIOT-011',
                'serial_number' => null,
                'logger_model' => 'RedNode Bliiot',
                'vendor' => 'Bliiot',
                'firmware_version' => 'Ubuntu 20.04.5 LTS',
                'device_label' => 'BL118-bliiot',
                'remote_host' => '192.168.3.1',
                'remote_ssh_port' => 22,
                'remote_ssh_user' => 'root',
                'remote_ssh_password' => null,
                'remote_gateway_path' => '/root/rednode-gateway',
                'remote_last_tested_at' => '2026-08-11 12:12:07',
                'remote_last_status' => 'Success',
                'remote_last_message' => 'Gateway heartbeat/config dari 192.168.3.1',
                'logger_status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:12:07'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('data_loggers')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}