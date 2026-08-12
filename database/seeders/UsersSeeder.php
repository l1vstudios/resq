<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
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
                'name' => 'Sentinal Admin',
                'email' => 'sentinaladmin@resq.com',
                'email_verified_at' => '2022-01-02 17:04:58',
                'type' => 'sentinel',
                'password' => '$2y$04$fK/U8ZQN01kw2lRyaktBo.WnQJITMG.sorQLH4qYcqDGnQZyMhrae',
                'dob' => '2000-10-10',
                'avatar' => 'images/avatar-1.jpg',
                'remember_token' => null,
                'created_at' => '2026-08-11 12:02:14',
                'updated_at' => '2026-08-11 12:02:14',
                'client_id' => null,
                'status' => 'active'
            ],
            [
                'id' => 6,
                'name' => 'Demo Sentinel Admin',
                'email' => 'sentinel.admin@resq.local',
                'email_verified_at' => '2026-08-11 08:00:00',
                'type' => 'sentinel',
                'password' => '$2y$10$vxIo/5dQ6tLABAbjZPj6uOrUD7QFHPgyDtdNYid1VGrXYBbE4Jkp2',
                'dob' => '2000-01-01',
                'avatar' => 'images/avatar-1.jpg',
                'remember_token' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02',
                'client_id' => null,
                'status' => 'active'
            ],
            [
                'id' => 7,
                'name' => 'Demo Client Admin',
                'email' => 'client.admin@resq.local',
                'email_verified_at' => '2026-08-11 12:08:02',
                'type' => 'client',
                'password' => '$2y$10$p5EzXUdwF8VmBQIU9fIkXO/nqD/5WGDvEIjjmnRFJWHqEqUf7w4me',
                'dob' => '2000-01-01',
                'avatar' => 'images/avatar-1.jpg',
                'remember_token' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02',
                'client_id' => 4,
                'status' => 'active'
            ],
            [
                'id' => 8,
                'name' => 'Demo Client Operator',
                'email' => 'client.operator@resq.local',
                'email_verified_at' => '2026-08-11 12:08:02',
                'type' => 'client',
                'password' => '$2y$10$yuB.gPCtFscQgX0t9.UGleffBn1LQVioR.v204fqL4vSIDm/ABK5.',
                'dob' => '2000-01-01',
                'avatar' => 'images/avatar-1.jpg',
                'remember_token' => null,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:08:02',
                'client_id' => 4,
                'status' => 'active'
            ]
        ];

        foreach ($data as $row) {
            DB::table('users')->insertOrIgnore($row);
        }
    }
}