<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RootUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('USER_PASSWORD_DEV', '12345678');

        User::updateOrCreate(
            ['email' => 'root@owomarket.local'],
            [
                'name' => 'Root',
                'password' => Hash::make($password),
            ]
        );
    }
}

