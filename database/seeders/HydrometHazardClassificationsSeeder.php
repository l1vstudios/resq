<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HydrometHazardClassificationsSeeder extends Seeder
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
                'hydromet_ews_relationship_id' => 4,
                'corridor_id' => 4,
                'monitoring_station_id' => 4,
                'sensor_id' => 7,
                'canonical_parameter_id' => 6,
                'classification_code' => 'HZ-DEMO-WL-01',
                'parameter' => 'Rainfall',
                'reading_method' => 'Absolute',
                'threshold_config' => '{"comparison": "configuration_only"}',
                'hazard_levels' => '{"AWAS": {"level": "AWAS", "threshold": 4}, "SIAGA": {"level": "SIAGA", "threshold": 3}, "WASPADA": {"level": "WASPADA", "threshold": 2}}',
                'unresolved_business_rules' => '["Comparison direction and persistence rules must be confirmed by hydromet authority."]',
                'evaluation_engine' => 'configuration_only',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ],
            [
                'id' => 2,
                'project_id' => 4,
                'hydromet_ews_relationship_id' => 4,
                'corridor_id' => 4,
                'monitoring_station_id' => 4,
                'sensor_id' => 8,
                'canonical_parameter_id' => 12,
                'classification_code' => 'HZ-DEMO-SOILMOISTURE-01',
                'parameter' => 'SoilMoisture',
                'reading_method' => 'Absolute',
                'threshold_config' => '{"basis": "Mapped SoilMoisture value from raw register multiplied by 0.1.", "comparison": ">="}',
                'hazard_levels' => '{"AWAS": {"level": "AWAS", "threshold": 35}, "SIAGA": {"level": "SIAGA", "threshold": 30}, "WASPADA": {"level": "WASPADA", "threshold": 25}}',
                'unresolved_business_rules' => '["Persistence and de-escalation windows must be confirmed by hydromet authority."]',
                'evaluation_engine' => 'configuration_only',
                'status' => 'active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('hydromet_hazard_classifications')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}