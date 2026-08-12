<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientsSeeder extends Seeder
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
                'client_code' => 'DEMO-CLIENT',
                'name' => 'Demo Client Sentinel EMP',
                'contact_name' => 'Demo PIC',
                'contact_email' => 'client.demo@resq.local',
                'contact_phone' => '+628000000001',
                'status' => 'active',
                'max_users' => 5,
                'max_projects' => 3,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27',
                'deleted_at' => null
            ]
        ];

        foreach ($data as $row) {
            DB::table('clients')->insertOrIgnore($row);
        }
    }
}