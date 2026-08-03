<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->keepLatestPerGroup('telemetry_readings', ['sensor_id']);
        $this->keepLatestPerGroup('canonical_observations', ['sensor_id', 'domain', 'sensor_mapping_profile_id']);
        $this->keepLatestPerGroup('raw_data_ingestions', ['sensor_id', 'source_parameter', 'register_address']);
    }

    public function down(): void
    {
        //
    }

    private function keepLatestPerGroup(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $keepIds = DB::table($table)
            ->selectRaw('MAX(id) as id')
            ->groupBy($columns)
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        if (empty($keepIds)) {
            return;
        }

        DB::table($table)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
};
