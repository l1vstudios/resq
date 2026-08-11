<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sensor_mapping_preset_items') && Schema::hasTable('sensor_mapping_presets')) {
            $presetId = DB::table('sensor_mapping_presets')
                ->where('preset_key', 'rika-rk900-11')
                ->value('id');

            if ($presetId) {
                DB::table('sensor_mapping_preset_items')
                    ->where('sensor_mapping_preset_id', $presetId)
                    ->where('value_type', 'float32')
                    ->update([
                        'byte_order' => 'CDAB',
                        'updated_at' => now(),
                    ]);
            }
        }

        if (Schema::hasTable('sensor_mapping_profiles')) {
            DB::table('sensor_mapping_profiles')
                ->where('device_model', 'RK900-11')
                ->where('value_type', 'float32')
                ->update([
                    'byte_order' => 'CDAB',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sensor_mapping_preset_items') && Schema::hasTable('sensor_mapping_presets')) {
            $presetId = DB::table('sensor_mapping_presets')
                ->where('preset_key', 'rika-rk900-11')
                ->value('id');

            if ($presetId) {
                DB::table('sensor_mapping_preset_items')
                    ->where('sensor_mapping_preset_id', $presetId)
                    ->where('value_type', 'float32')
                    ->update([
                        'byte_order' => null,
                        'updated_at' => now(),
                    ]);
            }
        }

        if (Schema::hasTable('sensor_mapping_profiles')) {
            DB::table('sensor_mapping_profiles')
                ->where('device_model', 'RK900-11')
                ->where('value_type', 'float32')
                ->update([
                    'byte_order' => null,
                    'updated_at' => now(),
                ]);
        }
    }
};
