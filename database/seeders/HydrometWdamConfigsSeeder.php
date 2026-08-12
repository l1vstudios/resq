<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HydrometWdamConfigsSeeder extends Seeder
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
                'id' => 4,
                'project_id' => 4,
                'hydromet_ews_relationship_id' => 4,
                'warning_station_id' => 4,
                'wdam_code' => 'WDAM-DEMO-01',
                'dashboard_notification_enabled' => 1,
                'registered_recipients' => '[{"name": "Demo Operator", "channel": "dashboard"}]',
                'sms_enabled' => 0,
                'sms_provider_ref' => null,
                'whatsapp_enabled' => 0,
                'whatsapp_provider_ref' => null,
                'warning_station_assignment_enabled' => 1,
                'automatic_activation_enabled' => 0,
                'authority_method' => 'manual_authority',
                'status' => 'active',
                'notes' => 'Provider credentials not selected in demo.',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        foreach ($data as $row) {
            DB::table('hydromet_wdam_configs')->insertOrIgnore($row);
        }
    }
}