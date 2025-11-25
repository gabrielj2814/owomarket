<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

class TenantDomainSeeder extends Seeder
{
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
        ];

        $centralDomains = config('tenancy.central_domains', []);

        // Prefer the first non-local domain as base (e.g., owomarket.test)
        $baseDomain = collect($centralDomains)
            ->first(function ($d) {
                return !in_array($d, ['localhost', '127.0.0.1'], true);
            }) ?? Arr::first($centralDomains) ?? 'localhost';

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
                    'name' => $name,
                    'slug' => $slug
                    // Los demás campos tienen default en la migración
                ]
            );

            $tenant->domains()->create([
                'id' => Str::uuid()->toString(),
                'domain' => $fullDomain
            ]);
        }
    }
}
