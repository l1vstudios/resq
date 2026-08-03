<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'sentinaladmin@resq.com'],
            [
                'name' => 'Sentinal Admin',
                'dob' => '2000-10-10',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
                'avatar' => 'images/avatar-1.jpg',
            ]
        );
    }
}
