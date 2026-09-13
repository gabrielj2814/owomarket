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

// Subsistema 5: el reloj de las reclamaciones. Resuelve a favor del comprador lo que la
// tienda no respondio dentro del plazo -- sin esto, ignorar una reclamacion sale gratis.
Illuminate\Support\Facades\Schedule::command('returns:auto-resolve')
    ->dailyAt('03:30')
    ->timezone('America/Caracas')
    ->withoutOverlapping();

// Notificaciones, fase 4: el segundo aviso de una reclamacion, un dia antes de que venza.
//
// **A las 03:00, ANTES que `returns:auto-resolve`.** El orden no es casual: si corriera
// despues, recordaria reclamaciones que el reloj acaba de resolver esa misma madrugada --un
// aviso para actuar sobre algo que ya no se puede tocar--.
//
// Es lo que convierte el reloj en justo: perder una venta por silencio tras dos avisos es una
// decision del comerciante; perderla con uno solo de hace cinco dias es un descuido que la
// plataforma podia haber evitado.
Illuminate\Support\Facades\Schedule::command('returns:remind-expiring')
    ->dailyAt('02:45')
    ->timezone('America/Caracas')
    ->withoutOverlapping();

// Notificaciones, fase 4: el techo mensual de alarma.
//
// **Diario, aunque el techo sea mensual.** Una comprobacion el dia 1 avisaria de un mes que ya
// termino, y el techo existe para poder intervenir ANTES de que el mes se descontrole. Lo que
// evita el correo diario es el freno --un aviso por mes-- no la frecuencia del comando.
Illuminate\Support\Facades\Schedule::command('coverage:check-ceiling')
    ->dailyAt('07:00')
    ->timezone('America/Caracas')
    ->withoutOverlapping();

// Subsistema 3: libera el dinero de las entregas que el comprador nunca confirmo. Una vez
// al dia basta --el plazo se mide en dias-- y `withoutOverlapping` evita que dos pasadas
// simultaneas intenten liberar el mismo expediente.
Illuminate\Support\Facades\Schedule::command('deliveries:release-unconfirmed')
    ->dailyAt('03:00')
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
