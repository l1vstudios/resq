<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SensorMappingPresetsSeeder extends Seeder
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
                'preset_key' => 'rika-rk900-11',
                'label' => 'RK900-11 Weather Station',
                'manufacturer' => 'Rika Sensor',
                'device_model' => 'RK900-11',
                'communication_path' => 'RS485 Modbus RTU',
                'description' => 'Preset parameter RK900-11 berdasarkan User Manual V5.0 bagian Communication Protocol MODBUS-RTU. Offset memakai Modbus address 0-based dari read block address 0.',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 2,
                'preset_key' => 'rika-rk510-01',
                'label' => 'RK510-01 Soil Moisture',
                'manufacturer' => 'Rika Sensor',
                'device_model' => 'RK510-01',
                'communication_path' => 'RS485 Modbus RTU',
                'description' => 'Preset parameter RK510-01 soil moisture berdasarkan tabel spesifikasi Rika.',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ]
        ];

        foreach ($data as $row) {
            DB::table('sensor_mapping_presets')->insertOrIgnore($row);
        }
    }
}