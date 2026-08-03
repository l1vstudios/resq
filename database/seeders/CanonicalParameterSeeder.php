<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CanonicalParameter;

class CanonicalParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parameters = [
            // METEOROLOGY DOMAIN
            [
                'field_identity' => 'Temperature',
                'definition' => 'Ambient air temperature',
                'domain' => 'meteorology',
                'canonical_unit' => '°C',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'Humidity',
                'definition' => 'Relative humidity of the air',
                'domain' => 'meteorology',
                'canonical_unit' => '%',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'Pressure',
                'definition' => 'Atmospheric pressure',
                'domain' => 'meteorology',
                'canonical_unit' => 'hPa',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'WindSpeed',
                'definition' => 'Speed of the wind',
                'domain' => 'meteorology',
                'canonical_unit' => 'm/s',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'WindDirection',
                'definition' => 'Direction from which the wind originates',
                'domain' => 'meteorology',
                'canonical_unit' => '°',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'Rainfall',
                'definition' => 'Amount of liquid precipitation',
                'domain' => 'meteorology',
                'canonical_unit' => 'mm',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'accumulated',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'SolarRadiation',
                'definition' => 'Power per unit area received from the Sun',
                'domain' => 'meteorology',
                'canonical_unit' => 'W/m²',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],

            // HYDROLOGY DOMAIN
            [
                'field_identity' => 'WaterLevel',
                'definition' => 'Elevation of the free surface of a body of water',
                'domain' => 'hydrology',
                'canonical_unit' => 'm',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'WaterVelocity',
                'definition' => 'Speed of the flowing water',
                'domain' => 'hydrology',
                'canonical_unit' => 'm/s',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'WaterDischarge',
                'definition' => 'Volumetric flow rate of water (calculated)',
                'domain' => 'hydrology',
                'canonical_unit' => 'm³/s',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => true,
                'source_fields' => ['WaterLevel', 'WaterVelocity'],
                'status' => 'active',
            ],
            [
                'field_identity' => 'TideLevel',
                'definition' => 'Sea water level relative to a datum',
                'domain' => 'hydrology',
                'canonical_unit' => 'm',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],

            // GEOTECHNICAL DOMAIN
            [
                'field_identity' => 'SoilMoisture',
                'definition' => 'Volumetric water content of soil',
                'domain' => 'geotechnical',
                'canonical_unit' => '%',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'GroundTiltX',
                'definition' => 'Tilt angle of the ground on the X-axis',
                'domain' => 'geotechnical',
                'canonical_unit' => '°',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'GroundTiltY',
                'definition' => 'Tilt angle of the ground on the Y-axis',
                'domain' => 'geotechnical',
                'canonical_unit' => '°',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'VibrationVelocity',
                'definition' => 'Peak Particle Velocity (PPV) of ground vibration',
                'domain' => 'geotechnical',
                'canonical_unit' => 'mm/s',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'peak',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'ExtensometerDisplacement',
                'definition' => 'Linear displacement measured by an extensometer',
                'domain' => 'geotechnical',
                'canonical_unit' => 'mm',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'accumulated',
                'is_platform_processed' => false,
                'status' => 'active',
            ],

            // SYSTEM/DEVICE HEALTH (Can fall under specific domains or a general one, we'll map to meteorology or hydrology based on the context, but let's put them in meteorology for now as a catch-all for weather stations, or create a new domain if allowed. The schema allows 'meteorology', 'hydrology', 'geotechnical'. We'll map battery to meteorology for weather station context)
            [
                'field_identity' => 'BatteryVoltage',
                'definition' => 'Voltage level of the device power source',
                'domain' => 'meteorology', // Using meteorology as a default for station health
                'canonical_unit' => 'V',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
            [
                'field_identity' => 'DeviceTemperature',
                'definition' => 'Internal temperature of the datalogger/sensor',
                'domain' => 'meteorology',
                'canonical_unit' => '°C',
                'data_type' => 'decimal',
                'measurement_characteristic' => 'instantaneous',
                'is_platform_processed' => false,
                'status' => 'active',
            ],
        ];

        foreach ($parameters as $param) {
            CanonicalParameter::updateOrCreate(
                ['field_identity' => $param['field_identity']],
                $param
            );
        }
    }
}
