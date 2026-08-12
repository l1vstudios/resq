<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SentinelNotificationsSeeder extends Seeder
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
                'user_id' => null,
                'client_id' => 4,
                'project_id' => 4,
                'corridor_id' => 4,
                'monitoring_station_id' => 4,
                'warning_station_id' => 4,
                'category' => 'Operational',
                'event_type' => 'Waspada',
                'title' => 'Demo Waspada WaterLevel',
                'body' => 'Demo notification for WaterLevel Waspada state.',
                'source_context' => '{"value": 2.4, "parameter": "WaterLevel"}',
                'occurred_at' => '2026-08-11 07:40:00',
                'read_at' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('sentinel_notifications')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}