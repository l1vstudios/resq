<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelHasRolesSeeder extends Seeder
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
                'id' => 5,
                'role_id' => 10,
                'model_type' => 'App\\Models\\User',
                'model_id' => 1,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 6,
                'role_id' => 10,
                'model_type' => 'App\\Models\\User',
                'model_id' => 6,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 7,
                'role_id' => 13,
                'model_type' => 'App\\Models\\User',
                'model_id' => 7,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ],
            [
                'id' => 8,
                'role_id' => 14,
                'model_type' => 'App\\Models\\User',
                'model_id' => 8,
                'created_at' => '2026-08-11 12:02:27',
                'updated_at' => '2026-08-11 12:02:27'
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($data as $row) {
            DB::table('model_has_roles')->insertOrIgnore($row);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}