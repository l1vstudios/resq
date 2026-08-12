<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StationFunctionConfigurationsSeeder extends Seeder
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
                'project_id' => 4,
                'monitoring_station_id' => 4,
                'function_name' => 'TDE',
                'reading_method' => 'Moving Average',
                'configuration' => '{"data_window": {"unit": "hours", "value": 6}}',
                'validation_state' => 'not_validated',
                'validated_at' => null,
                'activated_at' => null,
                'unresolved_analytical_rules' => '["TDE calculation formula is intentionally not implemented in demo seed."]',
                'status' => 'draft',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ],
            [
                'id' => 2,
                'project_id' => 4,
                'monitoring_station_id' => 4,
                'function_name' => 'Discharge',
                'reading_method' => 'Absolute',
                'configuration' => '{"unit": "m3/s", "manning_n": 0.031, "coefficient_cd": 0.72, "calculation_runtime": "backend_only", "cross_sectional_area": 12.5}',
                'validation_state' => 'not_validated',
                'validated_at' => null,
                'activated_at' => null,
                'unresolved_analytical_rules' => '["Discharge calculation formula is intentionally not implemented in demo seed."]',
                'status' => 'draft',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ],
            [
                'id' => 3,
                'project_id' => 4,
                'monitoring_station_id' => 4,
                'function_name' => 'CFPE',
                'reading_method' => 'Probability',
                'configuration' => '{"offset_distance": 3.5, "reference_bm_id": 1, "offset_direction": "centerline", "reference_route_id": 1, "uncertainty_factor": 0.2, "calculation_runtime": "backend_only", "station_ground_zero_chainage": 125.75}',
                'validation_state' => 'validated',
                'validated_at' => '2026-08-11 08:00:00',
                'activated_at' => '2026-08-11 08:00:00',
                'unresolved_analytical_rules' => '["CFPE calculation formula is intentionally not implemented in demo seed."]',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ]
        ];

        foreach ($data as $row) {
            DB::table('station_function_configurations')->insertOrIgnore($row);
        }
    }
}