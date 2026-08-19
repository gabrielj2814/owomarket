<?php

use Database\Seeders\TenantDomainSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tenants:seed-domains', function () {
    $this->info('Sembrando dominios de prueba para tenants...');
    $this->call('db:seed', ['--class' => TenantDomainSeeder::class, '--no-interaction' => true]);
    $this->info('Listo.');
})->purpose('Registra dominios de prueba en tenants');

// Sincronización automática de la tasa BCV en horarios bancarios de Venezuela
Illuminate\Support\Facades\Schedule::command('exchange-rate:sync-bcv')
    ->weekdays()
    ->at('09:00')
    ->at('13:00')
    ->at('17:30')
    ->timezone('America/Caracas')
    ->withoutOverlapping();
