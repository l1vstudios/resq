<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sensor_mapping_presets')) {
            Schema::create('sensor_mapping_presets', function (Blueprint $table) {
                $table->id();
                $table->string('preset_key')->unique();
                $table->string('label');
                $table->string('manufacturer')->nullable();
                $table->string('device_model')->nullable();
                $table->string('communication_path')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sensor_mapping_preset_items')) {
            Schema::create('sensor_mapping_preset_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sensor_mapping_preset_id')->constrained('sensor_mapping_presets')->cascadeOnDelete();
                $table->foreignId('canonical_parameter_id')->constrained('canonical_parameters')->cascadeOnDelete();
                $table->string('source_parameter');
                $table->string('source_unit')->nullable();
                $table->integer('register_offset')->default(0);
                $table->string('function_code')->nullable();
                $table->string('value_type')->nullable();
                $table->unsignedInteger('data_length')->nullable();
                $table->string('byte_order')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['sensor_mapping_preset_id', 'canonical_parameter_id'], 'preset_item_unique_parameter');
            });
        }

        $this->seedPreset(
            'rika-rk900-11',
            'RK900-11 Weather Station',
            'Rika Sensor',
            'RK900-11',
            'RS485 Modbus RTU',
            'Preset parameter RK900-11 berdasarkan User Manual V5.0 bagian Communication Protocol MODBUS-RTU. Offset memakai Modbus address 0-based dari read block address 0.',
            [
                ['WindDirection', 'Wind direction', '°', 1, 'FC03', 'uint16', 1],
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
            ]
        );

        $this->seedPreset(
            'rika-rk510-01',
            'RK510-01 Soil Moisture',
            'Rika Sensor',
            'RK510-01',
            'RS485 Modbus RTU',
            'Preset parameter RK510-01 soil moisture berdasarkan tabel spesifikasi Rika.',
            [
                ['SoilMoisture', 'Soil moisture', '%', 0, 'FC03', 'float32', 2],
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_mapping_preset_items');
        Schema::dropIfExists('sensor_mapping_presets');
    }

    private function seedPreset(
        string $presetKey,
        string $label,
        string $manufacturer,
        string $deviceModel,
        string $communicationPath,
        string $description,
        array $items
    ): void {
        $now = now();

        DB::table('sensor_mapping_presets')->updateOrInsert(
            ['preset_key' => $presetKey],
            [
                'label' => $label,
                'manufacturer' => $manufacturer,
                'device_model' => $deviceModel,
                'communication_path' => $communicationPath,
                'description' => $description,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $presetId = DB::table('sensor_mapping_presets')->where('preset_key', $presetKey)->value('id');

        foreach ($items as $sortOrder => $item) {
            [$fieldIdentity, $sourceParameter, $sourceUnit, $registerOffset] = $item;
            $functionCode = $item[4] ?? null;
            $valueType = $item[5] ?? null;
            $dataLength = $item[6] ?? null;
            $byteOrder = $item[7] ?? null;
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
};
