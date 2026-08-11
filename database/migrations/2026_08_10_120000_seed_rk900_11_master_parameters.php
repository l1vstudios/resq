<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::connection()->getPdo()) {
            return;
        }

        if (! DB::getSchemaBuilder()->hasTable('canonical_parameters')) {
            return;
        }

        $parameters = [
            [
                'field_identity' => 'WindSpeed',
                'definition' => 'Ultrasonic wind speed measurement from RK900-11',
                'domain' => 'meteorology',
                'canonical_unit' => 'm/s',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => 0,
                    'max_value' => 40,
                    'resolution' => 0.1,
                    'accuracy' => '±5%',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - Wind speed row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'WindDirection',
                'definition' => 'Ultrasonic wind direction measurement from RK900-11',
                'domain' => 'meteorology',
                'canonical_unit' => '°',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => 0,
                    'max_value' => 359,
                    'resolution' => 1,
                    'accuracy' => '±3°',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - Wind direction row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'Temperature',
                'definition' => 'Atmospheric temperature from RK900-11',
                'domain' => 'meteorology',
                'canonical_unit' => '°C',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => -40,
                    'max_value' => 80,
                    'resolution' => 0.1,
                    'accuracy' => '±1°C',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - Atmospheric temperature row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'Humidity',
                'definition' => 'Atmospheric relative humidity from RK900-11',
                'domain' => 'meteorology',
                'canonical_unit' => '%RH',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => 0,
                    'max_value' => 100,
                    'resolution' => 1,
                    'accuracy' => '±3%',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - Atmospheric humidity row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'Pressure',
                'definition' => 'Atmospheric pressure from RK900-11',
                'domain' => 'meteorology',
                'canonical_unit' => 'hPa',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => 300,
                    'max_value' => 1100,
                    'resolution' => 0.1,
                    'accuracy' => '±2hPa',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - Atmospheric pressure row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'Rainfall',
                'definition' => 'Rainfall rate (hourly) from RK900-11, valid at wind speed ≤5 m/s',
                'domain' => 'meteorology',
                'canonical_unit' => 'mm/hr',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'accumulated',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => 0,
                    'max_value' => 200,
                    'resolution' => 0.1,
                    'accuracy' => '±8% (at wind speed ≤5 m/s)',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - Rainfall row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'Altitude',
                'definition' => 'Altitude/elevation measurement from RK900-11 barometer',
                'domain' => 'meteorology',
                'canonical_unit' => 'm',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => -500,
                    'max_value' => 9000,
                    'resolution' => 1,
                    'accuracy' => '±8%',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - Altitude row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'Irradiance',
                'definition' => 'Solar irradiance (pyranometer) from RK900-11 at vertical light incidence',
                'domain' => 'meteorology',
                'canonical_unit' => 'W/m²',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => 0,
                    'max_value' => 2000,
                    'resolution' => 0.1,
                    'accuracy' => '±5% (at vertical light)',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - Irradiance row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'Illumination',
                'definition' => 'Illuminance (lux) from RK900-11 at vertical light incidence',
                'domain' => 'meteorology',
                'canonical_unit' => 'lux',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => 0,
                    'max_value' => 200000,
                    'resolution' => 0.1,
                    'accuracy' => '±5% (at vertical light)',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - Illumination row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'PM25',
                'definition' => 'Particulate matter ≤2.5 μm from RK900-11 air quality sensor',
                'domain' => 'meteorology',
                'canonical_unit' => 'μg/m³',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => 0,
                    'max_value' => 2000,
                    'resolution' => 1,
                    'accuracy' => '±5%',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - PM2.5 row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'PM10',
                'definition' => 'Particulate matter ≤10 μm from RK900-11 air quality sensor',
                'domain' => 'meteorology',
                'canonical_unit' => 'μg/m³',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => 0,
                    'max_value' => 2000,
                    'resolution' => 1,
                    'accuracy' => '±8%',
                    'source_url' => 'https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html',
                    'source_reference' => 'SPECIFICATIONS table - PM10 row',
                    'source_note' => 'RK900-11 official Rika Sensor product page',
                ]),
                'status' => 'active',
            ],
            [
                'field_identity' => 'SoilMoisture',
                'definition' => 'Volumetric soil moisture content measured by RK510-01 FDR soil moisture sensor',
                'domain' => 'geotechnical',
                'canonical_unit' => '%',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'input_requirements' => json_encode([
                    'min_value' => 0,
                    'max_value' => 100,
                    'resolution' => null,
                    'accuracy' => '±2% (0-50%)',
                    'source_url' => 'https://www.rikasensor.com/perfect-soil-moisture-sensor-manufacturer-for-soil-monitoring.html',
                    'source_reference' => 'SPECIFICATIONS table - Range / Accuracy rows',
                    'source_note' => 'RK510-01 official Rika Sensor product page; ranges available: 0-100%, 0-50%, 0-30%',
                ]),
                'status' => 'active',
            ],
        ];

        foreach ($parameters as $param) {
            DB::table('canonical_parameters')->updateOrInsert(
                ['field_identity' => $param['field_identity']],
                array_merge($param, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        if (! DB::connection()->getPdo()) {
            return;
        }

        if (! DB::getSchemaBuilder()->hasTable('canonical_parameters')) {
            return;
        }

        DB::table('canonical_parameters')->whereIn('field_identity', [
            'Altitude',
            'Irradiance',
            'Illumination',
            'PM25',
            'PM10',
            'SoilMoisture',
        ])->delete();
    }
};
