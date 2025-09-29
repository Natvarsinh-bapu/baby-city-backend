<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        //admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@babycity.in',
            'password' => bcrypt('y16f842v'),
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
