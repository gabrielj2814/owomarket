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

/*
|--------------------------------------------------------------------------
| Red de seguridad para la cola (hallazgos N17 y N25)
|--------------------------------------------------------------------------
|
| Desde que el despacho de pedidos centrales y la sincronización del catálogo van en
| cola, **sin un worker corriendo esas dos cosas no ocurren**: un pedido cobrado no
| llegaría nunca a su tienda. Y hoy no hay ningún `queue:work` en docker-compose ni en
| k8s, así que dejarlo a la infraestructura sería dejarlo roto.
|
| Esto lo cubre apoyándose en el scheduler, que sí está corriendo. `--stop-when-empty`
| hace que el proceso termine al vaciar la cola en vez de quedarse residente, y
| `withoutOverlapping` impide que se solapen dos pasadas.
|
| **No sustituye a un worker de verdad.** Con este apaño el peor caso de latencia es un
| minuto, lo cual es aceptable para el despacho y la sincronización, pero conviene
| montar un proceso dedicado (supervisor, o un contenedor aparte) al abordar el
| despliegue. Cuando exista, esta entrada se puede borrar.
*/
Illuminate\Support\Facades\Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
