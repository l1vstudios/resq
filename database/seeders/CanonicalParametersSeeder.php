<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CanonicalParametersSeeder extends Seeder
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
                'field_identity' => 'WindSpeed',
                'definition' => 'Ultrasonic wind speed measurement from RK900-11',
                'domain' => 'meteorology',
                'canonical_unit' => 'm/s',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±5%", "max_value": 40, "min_value": 0, "resolution": 0.1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - Wind speed row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 2,
                'field_identity' => 'WindDirection',
                'definition' => 'Ultrasonic wind direction measurement from RK900-11',
                'domain' => 'meteorology',
                'canonical_unit' => '°',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±3°", "max_value": 359, "min_value": 0, "resolution": 1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - Wind direction row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 3,
                'field_identity' => 'Temperature',
                'definition' => 'Atmospheric temperature from RK900-11',
                'domain' => 'meteorology',
                'canonical_unit' => '°C',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±1°C", "max_value": 80, "min_value": -40, "resolution": 0.1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - Atmospheric temperature row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 4,
                'field_identity' => 'Humidity',
                'definition' => 'Atmospheric relative humidity from RK900-11',
                'domain' => 'meteorology',
                'canonical_unit' => '%RH',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±3%", "max_value": 100, "min_value": 0, "resolution": 1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - Atmospheric humidity row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 5,
                'field_identity' => 'Pressure',
                'definition' => 'Atmospheric pressure from RK900-11',
                'domain' => 'meteorology',
                'canonical_unit' => 'hPa',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±2hPa", "max_value": 1100, "min_value": 300, "resolution": 0.1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - Atmospheric pressure row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 6,
                'field_identity' => 'Rainfall',
                'definition' => 'Rainfall rate (hourly) from RK900-11, valid at wind speed ≤5 m/s',
                'domain' => 'meteorology',
                'canonical_unit' => 'mm/hr',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'accumulated',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±8% (at wind speed ≤5 m/s)", "max_value": 200, "min_value": 0, "resolution": 0.1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - Rainfall row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 7,
                'field_identity' => 'Altitude',
                'definition' => 'Altitude/elevation measurement from RK900-11 barometer',
                'domain' => 'meteorology',
                'canonical_unit' => 'm',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±8%", "max_value": 9000, "min_value": -500, "resolution": 1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - Altitude row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 8,
                'field_identity' => 'Irradiance',
                'definition' => 'Solar irradiance (pyranometer) from RK900-11 at vertical light incidence',
                'domain' => 'meteorology',
                'canonical_unit' => 'W/m²',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±5% (at vertical light)", "max_value": 2000, "min_value": 0, "resolution": 0.1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - Irradiance row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 9,
                'field_identity' => 'Illumination',
                'definition' => 'Illuminance (lux) from RK900-11 at vertical light incidence',
                'domain' => 'meteorology',
                'canonical_unit' => 'lux',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±5% (at vertical light)", "max_value": 200000, "min_value": 0, "resolution": 0.1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - Illumination row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 10,
                'field_identity' => 'PM25',
                'definition' => 'Particulate matter ≤2.5 μm from RK900-11 air quality sensor',
                'domain' => 'meteorology',
                'canonical_unit' => 'μg/m³',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±5%", "max_value": 2000, "min_value": 0, "resolution": 1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - PM2.5 row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 11,
                'field_identity' => 'PM10',
                'definition' => 'Particulate matter ≤10 μm from RK900-11 air quality sensor',
                'domain' => 'meteorology',
                'canonical_unit' => 'μg/m³',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±8%", "max_value": 2000, "min_value": 0, "resolution": 1, "source_url": "https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html", "source_note": "RK900-11 official Rika Sensor product page", "source_reference": "SPECIFICATIONS table - PM10 row"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 12,
                'field_identity' => 'SoilMoisture',
                'definition' => 'Volumetric soil moisture content measured by RK510-01 FDR soil moisture sensor',
                'domain' => 'geotechnical',
                'canonical_unit' => '%',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => '{"accuracy": "±2% (0-50%)", "max_value": 100, "min_value": 0, "resolution": null, "source_url": "https://www.rikasensor.com/perfect-soil-moisture-sensor-manufacturer-for-soil-monitoring.html", "source_note": "RK510-01 official Rika Sensor product page; ranges available: 0-100%, 0-50%, 0-30%", "source_reference": "SPECIFICATIONS table - Range / Accuracy rows"}',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:16',
                'updated_at' => '2026-08-11 12:02:16'
            ],
            [
                'id' => 13,
                'field_identity' => 'WaterLevel',
                'definition' => 'WaterLevel',
                'domain' => 'hydrology',
                'canonical_unit' => 'm',
                'data_type' => 'numeric',
                'measurement_characteristic' => 'measured',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => null,
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 14,
                'field_identity' => 'WaterVelocity',
                'definition' => 'WaterVelocity',
                'domain' => 'hydrology',
                'canonical_unit' => 'm/s',
                'data_type' => 'numeric',
                'measurement_characteristic' => 'measured',
                'is_platform_processed' => 0,
                'source_fields' => null,
                'formula' => null,
                'input_requirements' => null,
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('canonical_parameters')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}