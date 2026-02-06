<?php

// namespace Database\Seeders;

// use Illuminate\Database\Seeder;
// use Illuminate\Support\Arr;
// use Illuminate\Support\Str;
// use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
// use Stancl\Tenancy\Database\Models\Domain;

// class TenantDomainSeeder extends Seeder
// {
//     public function run(): void
//     {
//         $codes = [
//             'tecs',
//             'anicomacarigua',
//             'chivostore',
//             'tecno_isekaic',
//             'tematicosvzla',
//             'cosplay_',
//             'darker',
//             'montcord_sc',
//             'baymax',
//             // '7-leven',
//         ];

//         $centralDomains = config('tenancy.central_domains', []);

//         // Prefer the first non-local domain as base (e.g., owomarket.test)
//         $baseDomain = collect($centralDomains)
//             ->first(function ($d) {
//                 return !in_array($d, ['localhost', '127.0.0.1'], true);
//             }) ?? Arr::first($centralDomains) ?? 'localhost';

//         foreach ($codes as $code) {
//             $sub = Str::lower($code);
//             $slug = Str::slug($code);
//             $name = $code; // usar el valor de codes como nombre
//             $fullDomain = sprintf('%s.%s', $sub, $baseDomain);

//             // Evitar duplicados por dominio
//             if (Domain::where('domain', $fullDomain)->exists()) {
//                 continue;
//             }

//             // Crear o reutilizar tenant por slug y asignar nombre
//             $tenant = Tenant::firstOrCreate(
//                 ['slug' => $slug],
//                 [
//                     'name'    => $name,
//                     'slug'    => $slug,
//                     'status'  => "active",
//                     'request' => "approved",
//                     // Los demás campos tienen default en la migración
//                 ]
//             );

//             $tenant->domains()->create([
//                 'id' => Str::uuid()->toString(),
//                 'domain' => $fullDomain
//             ]);
//         }
//     }
// }

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Src\Tenant\Domain\Shared\Security\PasswordHasher;
use Src\Tenant\Domain\Shared\Security\PasswordValidator;
use Src\Tenant\Domain\ValuesObjects\Password;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

class TenantDomainSeeder extends Seeder
{

    public function __construct(
        protected PasswordValidator $validator,
        protected PasswordHasher $hasher
    ){}

    public function run(): void
    {
        $codes = [
            'tecs',
            'anicomacarigua',
            'chivostore',
            'tecno_isekaic',
            'tematicosvzla',
            'cosplay_',
            'darker',
            'montcord_sc',
            'baymax',
            // '7-leven',
        ];

        $centralDomains = config('tenancy.central_domains', []);

        // Prefer the first non-local domain as base (e.g., owomarket.test)
        $baseDomain = collect($centralDomains)
            ->first(function ($d) {
                return !in_array($d, ['localhost', '127.0.0.1'], true);
            }) ?? Arr::first($centralDomains) ?? 'localhost';

        // Contraseña por defecto del usuario admin
        $defaultPassword = env('DEFAULT_USER_TENANT_OWNER_PASSWORD_DEV', 'EndAdmin_12345678');

        foreach ($codes as $code) {
            $sub = Str::lower($code);
            $slug = Str::slug($code);
            $name = $code; // usar el valor de codes como nombre
            $fullDomain = sprintf('%s.%s', $sub, $baseDomain);

            // Evitar duplicados por dominio
            if (Domain::where('domain', $fullDomain)->exists()) {
                continue;
            }

            // Crear o reutilizar tenant por slug y asignar nombre
            $tenant = Tenant::firstOrCreate(
                ['slug' => $slug],
                [
                    'name'    => $name,
                    'slug'    => $slug,
                    'status'  => "active",
                    'request' => "approved",
                    // Los demás campos tienen default en la migración
                ]
            );

            $tenant->domains()->create([
                'id' => Str::uuid()->toString(),
                'domain' => $fullDomain
            ]);

            // Crear usuario admin por defecto en la base de datos del tenant
            $this->createDefaultUserForTenant($tenant, $defaultPassword);
        }
    }

    /**
     * Crear usuario admin por defecto en la base de datos del tenant
     */
    private function createDefaultUserForTenant(Tenant $tenant, string $defaultPassword): void
    {
        // Ejecutar en el contexto del tenant
        tenancy()->initialize($tenant);

        try {
            // Verificar si ya existe un usuario admin
            $existingAdmin = \App\Models\User::where('email', 'admin@' . $tenant->slug . '.com')
                ->where('type', 'tenant_owner')
                ->first();

            if (!$existingAdmin) {

                // Crear usuario admin
                \App\Models\User::create([
                    'id' => Str::uuid()->toString(),
                    'name' => 'Administrador',
                    'email' => 'admin@' . $tenant->slug . '.com',
                    'password' => Password::fromPlainText($defaultPassword, $this->validator, $this->hasher)->getHash(),
                    'type' => 'tenant_owner',
                    'is_active' => true,
                    'country_id' => null,
                    'email_verified_at' => now(),
                ]);

                $this->command->info("Usuario admin creado para tenant: {$tenant->name}");
            }
        } finally {
            // Siempre finalizar el tenancy
            tenancy()->end();
        }
    }
}
