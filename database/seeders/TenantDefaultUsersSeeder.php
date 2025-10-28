<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantDefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('USER_PASSWORD_DEV', '12345678');

        $owners = [
            'tecs' => 'Kō Yamori',
            'chivostore' => 'Kyouko Mejiro',
            'tecno_isekaic' => 'Nazuna Nanakusa',
            'tematicosvzla' => 'arturia',
            'cosplay_' => 'modred',
            'darker' => 'astolfo',
            'montcord_sc' => 'stay gold',
            'baymax' => 'Akira Asai',
        ];

        foreach ($owners as $code => $name) {
            $slug = Str::slug($code);

            $tenant = Tenant::where('slug', $slug)->first();

            // Fallback: try by original name if slug not found
            if (! $tenant) {
                $tenant = Tenant::where('name', $code)->first();
            }

            if (! $tenant) {
                continue; // Tenant not present yet
            }

            $emailSlug = $slug !== '' ? $slug : Str::slug($tenant->name ?: 'tenant');
            $email = $emailSlug . '.owner@owomarket.local';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                ]
            );

            // Attach as owner via pivot table tenant_users
            $tenant->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => 'owner',
                    'permissions' => null,
                ],
            ]);
        }
    }
}

