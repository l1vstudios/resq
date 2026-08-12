<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SensorMappingPresetItemsSeeder extends Seeder
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
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 2,
                'source_parameter' => 'Wind direction',
                'source_unit' => '°',
                'register_offset' => 1,
                'function_code' => 'FC03',
                'value_type' => 'uint16',
                'data_length' => 1,
                'byte_order' => null,
                'sort_order' => 0,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 2,
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 1,
                'source_parameter' => 'Wind speed',
                'source_unit' => 'm/s',
                'register_offset' => 2,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => 'CDAB',
                'sort_order' => 1,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 3,
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 3,
                'source_parameter' => 'Atmospheric temperature',
                'source_unit' => '°C',
                'register_offset' => 4,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => 'CDAB',
                'sort_order' => 2,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 4,
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 4,
                'source_parameter' => 'Atmospheric humidity',
                'source_unit' => '%RH',
                'register_offset' => 6,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => 'CDAB',
                'sort_order' => 3,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 5,
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 5,
                'source_parameter' => 'Atmospheric pressure',
                'source_unit' => 'hPa',
                'register_offset' => 8,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => 'CDAB',
                'sort_order' => 4,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 6,
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 6,
                'source_parameter' => 'Rainfall',
                'source_unit' => 'mm/hr',
                'register_offset' => 12,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => 'CDAB',
                'sort_order' => 5,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 7,
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 10,
                'source_parameter' => 'Dust concentration (PM2.5)',
                'source_unit' => 'μg/m³',
                'register_offset' => 25,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => 'CDAB',
                'sort_order' => 6,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 8,
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 9,
                'source_parameter' => 'Illumination',
                'source_unit' => 'lux',
                'register_offset' => 29,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => 'CDAB',
                'sort_order' => 7,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 9,
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 8,
                'source_parameter' => 'Radiation',
                'source_unit' => 'W/m²',
                'register_offset' => 33,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => 'CDAB',
                'sort_order' => 8,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 10,
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 7,
                'source_parameter' => 'Altitude',
                'source_unit' => 'm',
                'register_offset' => 37,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => 'CDAB',
                'sort_order' => 9,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 11,
                'sensor_mapping_preset_id' => 1,
                'canonical_parameter_id' => 11,
                'source_parameter' => 'PM10',
                'source_unit' => 'μg/m³',
                'register_offset' => 47,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => 'CDAB',
                'sort_order' => 10,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 12,
                'sensor_mapping_preset_id' => 2,
                'canonical_parameter_id' => 12,
                'source_parameter' => 'Soil moisture',
                'source_unit' => '%',
                'register_offset' => 0,
                'function_code' => 'FC03',
                'value_type' => 'float32',
                'data_length' => 2,
                'byte_order' => null,
                'sort_order' => 0,
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ]
        ];

        foreach ($data as $row) {
            DB::table('sensor_mapping_preset_items')->insertOrIgnore($row);
        }
    }
}