<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Src\Admin\Domain\Shared\Security\PasswordHasher;
use Src\Admin\Domain\Shared\Security\PasswordValidator;
use Src\Admin\Domain\ValueObjects\Password;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

class TenantDefaultUsersSeeder extends Seeder
{


    public function __construct(
        protected PasswordValidator $validator,
        protected PasswordHasher $hasher
    ){}

    public function run(): void
    {
        $password = env('USER_PASSWORD_DEV', '12345678');

        $owners = [
            'tecs'              => 'Kō Yamori',
            'chivostore'        => 'Kyouko Mejiro',
            // '7-leven'           => 'Kyouko Mejiro',
            'tecno_isekaic'     => 'Nazuna Nanakusa',
            'tematicosvzla'     => 'arturia',
            'cosplay_'          => 'modred',
            'darker'            => 'astolfo',
            'montcord_sc'       => 'stay gold',
            'baymax'            => 'Akira Asai',
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
                    'id' =>   Str::uuid()->toString(),
                    'name' => $name,
                    'password' => Password::fromPlainText($password, $this->validator, $this->hasher)->getHash(),
                    'avatar' => "https://i.pinimg.com/originals/b0/ce/76/b0ce76f4cdb95ef13afa21a889adfc71.jpg",
                    'type' => "tenant_owner",
                ]
            );

            // Attach as owner via pivot table tenant_users
            $tenant->users()->syncWithoutDetaching([
                $user->id => [
                    'id' => Str::uuid()->toString(),
                    'role' => 'owner',
                    'permissions' => null,
                ],
            ]);
        }
    }
}

