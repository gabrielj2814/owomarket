<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Tarea 2 de la auditoria — nombres de ruta unicos
|--------------------------------------------------------------------------
|
| `src/SupportTicket/.../web.php` se montaba dos veces desde `routes/web.php`, en la raiz y
| bajo `/tenant`, para que cada vista quedara en su sitio. El efecto colateral era que
| TODAS sus rutas se registraban dos veces y los nombres colisionaban. Laravel no avisa:
| gana el ultimo, asi que `route('central.customer.support')` devolvia
| `/tenant/account/support` — una URL que existia pero no era la del portal del cliente.
|
| Nadie llamaba a ese `route()` todavia, asi que el fallo estaba esperando al primero que
| lo hiciera.
*/

test('cada nombre de ruta de soporte apunta a su URL real', function () {
    expect(route('central.customer.support', absolute: false))->toBe('/account/support');

    expect(route('central.backoffice.web.tenant.owner.support', ['user_uuid' => 'abc'], absolute: false))
        ->toBe('/tenant/owner/backoffice/abc/support');
});

test('las rutas de soporte se registran una sola vez', function () {
    // La causa raiz era el registro doble. Contar es lo que la detecta: si alguien vuelve
    // a montar el archivo dos veces, cada URI aparecera repetida y los nombres volveran a
    // pisarse en silencio.
    $uris = collect(Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
        // El dominio entra en la clave: las rutas centrales se registran una vez por cada
        // dominio de `tenancy.central_domains`, y eso es legitimo.
        ->map(fn ($ruta) => $ruta->methods()[0].' '.($ruta->domain() ?? '-').' '.$ruta->uri())
        ->filter(fn (string $uri) => str_contains($uri, 'api/support/') || str_contains($uri, '/support'));

    expect($uris->count())->toBe($uris->unique()->count());
});
