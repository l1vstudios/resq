<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MstPrefixesSeeder extends Seeder
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
                'prefix_code' => 'DEMO',
                'name' => 'Demo Modbus Prefix',
                'description' => null,
                'status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 5,
                'prefix_code' => 'RIKA',
                'name' => 'Rika Modbus Sensor',
                'description' => null,
                'status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 6,
                'prefix_code' => 'LEMBAB',
                'name' => 'Soil Moisture Sensor',
                'description' => null,
                'status' => 'Active',
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        foreach ($data as $row) {
            DB::table('mst_prefixes')->insertOrIgnore($row);
        }
    }
}