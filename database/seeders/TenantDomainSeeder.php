<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Tenant;
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
            $fullDomain = sprintf('%s.%s', $sub, $baseDomain);

            // Idempotent: only create when the domain does not exist yet
            if (!Domain::where('domain', $fullDomain)->exists()) {
                $tenant = Tenant::create();
                $tenant->domains()->create(['domain' => $fullDomain]);
            }
        }
    }
}
