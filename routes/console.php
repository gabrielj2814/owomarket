<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\TenantDomainSeeder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('tenants:seed-domains', function () {
    $this->info('Sembrando dominios de prueba para tenants...');
    $this->call('db:seed', ['--class' => TenantDomainSeeder::class, '--no-interaction' => true]);
    $this->info('Listo.');
})->purpose('Registra dominios de prueba en tenants');
