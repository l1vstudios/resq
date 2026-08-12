<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SensorsSeeder extends Seeder
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
                'id' => 7,
                'workspace_id' => 4,
                'monitoring_station_id' => 4,
                'data_logger_id' => 5,
                'warning_station_id' => null,
                'mst_prefix_id' => 5,
                'slave_id' => '1',
                'address' => '0',
                'function_code' => 'FC03',
                'quantity' => 49,
                'poll_interval_ms' => 1000,
                'sensor_code' => 'RIKA-CUACA',
                'type' => 'weather_station',
                'parameter' => 'Weather Station',
                'weather_parameters' => null,
                'value' => 'WindDirection 340.00 °, WindSpeed 0.00 m/s, Temperature 32.05 °C +8 parameter',
                'threshold' => null,
                'data_type' => 'float32',
                'scale_factor' => '1.0000',
                'offset' => '0.0000',
                'unit' => '',
                'reading_method' => 'Absolute',
                'alert_level' => 'Normal',
                'rule' => null,
                'status' => 'Normal',
                'last_seen_at' => '2026-08-11 12:12:08',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:12:07'
            ],
            [
                'id' => 8,
                'workspace_id' => 4,
                'monitoring_station_id' => 4,
                'data_logger_id' => 5,
                'warning_station_id' => null,
                'mst_prefix_id' => 6,
                'slave_id' => '4',
                'address' => '0',
                'function_code' => 'FC03',
                'quantity' => 1,
                'poll_interval_ms' => 1000,
                'sensor_code' => 'SENSOR-LEMBAB',
                'type' => 'soil_moisture',
                'parameter' => 'Soil Moisture',
                'weather_parameters' => null,
                'value' => 'SoilMoisture 39.40 %',
                'threshold' => '35',
                'data_type' => 'uint16',
                'scale_factor' => '0.1000',
                'offset' => '0.0000',
                'unit' => '%',
                'reading_method' => 'Absolute',
                'alert_level' => 'Awas',
                'rule' => 'AWAS when mapped SoilMoisture >= 35%',
                'status' => 'Awas',
                'last_seen_at' => '2026-08-11 12:12:08',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:12:08'
            ]
        ];

        foreach ($data as $row) {
            DB::table('sensors')->insertOrIgnore($row);
        }
    }
}