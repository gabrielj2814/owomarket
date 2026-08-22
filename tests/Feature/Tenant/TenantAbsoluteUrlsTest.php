<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/*
|--------------------------------------------------------------------------
| Tarea 1 de la auditoria — las URLs absolutas siguen a la tienda
|--------------------------------------------------------------------------
|
| `AppServiceProvider::boot()` hacia `if (tenancy()->initialized)`, que en el arranque del
| framework es SIEMPRE false: la tenancy se inicializa despues, en el middleware. Asi que
| el `forceRootUrl` no se aplicaba nunca y toda URL absoluta de un dominio de tienda salia
| con el `APP_URL` central — enlaces de correo incluidos.
|
| Importa mas desde N17 y N25: los jobs de la cola inicializan tenancy fuera de una
| peticion, y un correo enviado desde ahi tiene que enlazar a su tienda.
*/

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    $tenantId = 't_urls_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda URLs',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->dominio = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->dominio,
    ]);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('una URL absoluta dentro de una tienda usa el dominio de la tienda', function () {
    tenancy()->initialize($this->tenant);

    expect(url('/pedido/123'))->toContain($this->dominio);
});

test('al salir de la tienda se vuelve al dominio central', function () {
    // Sin esto, un worker que procesa un job de la tienda A y despues trabajo central
    // seguiria generando enlaces apuntando a A.
    $central = url('/pedido/123');

    tenancy()->initialize($this->tenant);
    tenancy()->end();

    expect(url('/pedido/123'))->toBe($central)
        ->and(url('/pedido/123'))->not->toContain($this->dominio);
});
