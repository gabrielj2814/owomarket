<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Pendiente P2 / hallazgo N23: `domains.id` es un `uuid` pero el modelo de Stancl declara
 * la clave primaria como entera autoincremental, así que Eloquent la casteaba y
 * `$domain->id` devolvía **siempre `0`**.
 *
 * Con la mayoría de UUID el fallo era silencioso; cuando el UUID empieza por dígitos
 * seguidos de `e` PHP lo lee como notación científica y la petición devuelve 500.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    $this->tenant = ModelsTenant::create([
        'id' => 't_dom_'.bin2hex(random_bytes(3)),
        'name' => 'Tienda Dominio',
        'slug' => 't_dom_'.bin2hex(random_bytes(3)),
        'status' => 'active',
        'request' => 'approved',
    ]);
});

test('el id de un dominio se lee como el UUID que hay en la base', function () {
    $uuid = 'a1b2c3d4-1111-4222-8333-444455556666';

    $this->tenant->domains()->create(['id' => $uuid, 'domain' => 'tienda-uuid.localhost']);

    $domain = $this->tenant->fresh()->domains->first();

    expect($domain->id)->toBe($uuid)
        ->and($domain->getKey())->toBe($uuid);
});

// El caso que provocaba el 500 intermitente: dígitos, luego `e`, luego dígitos.
test('un UUID con forma de notación científica no revienta la petición', function () {
    $uuid = '26e63005-1338-47cb-a8fc-33a1e2ee8e69';

    $this->tenant->domains()->create(['id' => $uuid, 'domain' => 'tienda-cientifica.localhost']);

    $domain = $this->tenant->fresh()->domains->first();

    expect($domain->id)->toBe($uuid);
});

test('guardar un dominio existente afecta a su propia fila', function () {
    $uuid = (string) Str::uuid();

    $this->tenant->domains()->create(['id' => $uuid, 'domain' => 'antes.localhost']);

    $domain = $this->tenant->fresh()->domains->first();
    $domain->domain = 'despues.localhost';
    $domain->save();

    // Con la clave a 0 esto generaba `WHERE id = 0`: no afectaba a ninguna fila.
    expect($this->tenant->fresh()->domains->first()->domain)->toBe('despues.localhost');
});
