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
| La cola la trabaja Horizon (hallazgo N40)
|--------------------------------------------------------------------------
|
| Aqui hubo un `queue:work --stop-when-empty` cada minuto, apoyado en el scheduler. Era
| un apano deliberado: el despacho de pedidos y la sincronizacion del catalogo pasaron a
| la cola con N17 y N25, y no habia ningun worker en el despliegue, asi que sin el un
| pedido cobrado no llegaba nunca a su tienda.
|
| Ya hay worker: el servicio `horizon` de docker-compose y el Deployment
| `owomarket-horizon` de k8s. Se retira el apano para que no haya dos procesos tirando de
| la misma cola — con Redis no se pisarian, pero tener dos cosas haciendo el mismo trabajo
| es como se acaba depurando el fantasma equivocado a las tres de la manana.
|
| Fuera de Docker (Laragon, sin Redis) la cola sigue en `database`; ahi se levanta a mano
| con `php artisan queue:work` cuando haga falta.
*/
