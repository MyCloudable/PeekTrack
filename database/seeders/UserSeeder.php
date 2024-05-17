<?php

namespace Database\Seeders;
use App\Models\User;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create([
            'name' => 'Admin',
            'role_id' => 1,
            'email' => 'admin@peeksafety.com',
            'password' => ('secret'),
            'picture' => 'profile/avatar.jpg'
        ]);

        User::factory()->create([
            'name' => 'Creator',
            'role_id' => 2,
            'email' => 'reviewer@peeksafety.com',
            'password' => ('secret'),
            'picture' => 'profile/avatar2.jpg'
        ]);

        User::factory()->create([
            'name' => 'Member',
            'role_id' => 3,
            'email' => 'superintendent@peeksafety.com',
            'password' => ('secret'),
            'picture' => 'profile/avatar3.jpg'
        ]);
    }
}
