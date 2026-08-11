<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sensor_mapping_preset_items')) {
            return;
        }

        Schema::table('sensor_mapping_preset_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sensor_mapping_preset_items', 'function_code')) {
                $table->string('function_code')->nullable()->after('register_offset');
            }
            if (! Schema::hasColumn('sensor_mapping_preset_items', 'value_type')) {
                $table->string('value_type')->nullable()->after('function_code');
            }
            if (! Schema::hasColumn('sensor_mapping_preset_items', 'data_length')) {
                $table->unsignedInteger('data_length')->nullable()->after('value_type');
            }
            if (! Schema::hasColumn('sensor_mapping_preset_items', 'byte_order')) {
                $table->string('byte_order')->nullable()->after('data_length');
            }
        });

        if (! Schema::hasTable('sensor_mapping_presets') || ! Schema::hasTable('canonical_parameters')) {
            return;
        }

        DB::table('sensor_mapping_presets')
            ->where('preset_key', 'rika-rk900-11')
            ->update([
                'description' => 'Preset parameter RK900-11 berdasarkan User Manual V5.0 bagian Communication Protocol MODBUS-RTU. Offset memakai Modbus address 0-based dari read block address 0.',
                'updated_at' => now(),
            ]);

        $presetId = DB::table('sensor_mapping_presets')
            ->where('preset_key', 'rika-rk900-11')
            ->value('id');

        if (! $presetId) {
            return;
        }

        $items = [
            ['WindDirection', 'Wind direction', '°', 1, 'FC03', 'uint16', 1, null],
            ['WindSpeed', 'Wind speed', 'm/s', 2, 'FC03', 'float32', 2, 'CDAB'],
            ['Temperature', 'Atmospheric temperature', '°C', 4, 'FC03', 'float32', 2, 'CDAB'],
            ['Humidity', 'Atmospheric humidity', '%RH', 6, 'FC03', 'float32', 2, 'CDAB'],
            ['Pressure', 'Atmospheric pressure', 'hPa', 8, 'FC03', 'float32', 2, 'CDAB'],
            ['Rainfall', 'Rainfall', 'mm/hr', 12, 'FC03', 'float32', 2, 'CDAB'],
            ['PM25', 'Dust concentration (PM2.5)', 'μg/m³', 25, 'FC03', 'float32', 2, 'CDAB'],
            ['Illumination', 'Illumination', 'lux', 29, 'FC03', 'float32', 2, 'CDAB'],
            ['Irradiance', 'Radiation', 'W/m²', 33, 'FC03', 'float32', 2, 'CDAB'],
            ['Altitude', 'Altitude', 'm', 37, 'FC03', 'float32', 2, 'CDAB'],
            ['PM10', 'PM10', 'μg/m³', 47, 'FC03', 'float32', 2, 'CDAB'],
        ];

        $now = now();

        foreach ($items as $sortOrder => [$fieldIdentity, $sourceParameter, $sourceUnit, $registerOffset, $functionCode, $valueType, $dataLength, $byteOrder]) {
            $parameterId = DB::table('canonical_parameters')
                ->where('field_identity', $fieldIdentity)
                ->value('id');

            if (! $parameterId) {
                continue;
            }

            DB::table('sensor_mapping_preset_items')->updateOrInsert(
                [
                    'sensor_mapping_preset_id' => $presetId,
                    'canonical_parameter_id' => $parameterId,
                ],
                [
                    'source_parameter' => $sourceParameter,
                    'source_unit' => $sourceUnit,
                    'register_offset' => $registerOffset,
                    'function_code' => $functionCode,
                    'value_type' => $valueType,
                    'data_length' => $dataLength,
                    'byte_order' => $byteOrder,
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sensor_mapping_preset_items')) {
            return;
        }

        $presetId = DB::table('sensor_mapping_presets')
            ->where('preset_key', 'rika-rk900-11')
            ->value('id');

        if (! $presetId) {
            return;
        }

        $legacyOffsets = [
            'WindSpeed' => 0,
            'WindDirection' => 1,
            'Temperature' => 2,
            'Humidity' => 3,
            'Pressure' => 4,
            'Rainfall' => 5,
            'Altitude' => 6,
            'Irradiance' => 7,
            'Illumination' => 8,
            'PM25' => 9,
            'PM10' => 10,
        ];

        foreach ($legacyOffsets as $fieldIdentity => $offset) {
            $parameterId = DB::table('canonical_parameters')
                ->where('field_identity', $fieldIdentity)
                ->value('id');

            if (! $parameterId) {
                continue;
            }

            DB::table('sensor_mapping_preset_items')
                ->where('sensor_mapping_preset_id', $presetId)
                ->where('canonical_parameter_id', $parameterId)
                ->update([
                    'register_offset' => $offset,
                    'function_code' => null,
                    'value_type' => null,
                    'data_length' => null,
                    'byte_order' => null,
                    'updated_at' => now(),
                ]);
        }
    }
};
