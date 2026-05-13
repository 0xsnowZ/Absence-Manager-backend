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
            ['email' => 'admin@school.ma'],
            [
                'name'     => 'Administrateur',
                'email'    => 'admin@school.ma',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ]
        );
    }
}
