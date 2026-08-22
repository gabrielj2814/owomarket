<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Hallazgo G7: el frontend deducía si estaba en el dominio central contando las etiquetas
 * del hostname (`parts.length <= 2`). Una tienda con dominio propio, `mitienda.com`, se
 * clasificaba como central: no generaba ni consumía el token SSO, así que no se creaba
 * sesión de cliente en la tienda. El usuario veía «Conectado con OwO Pass» en el checkout
 * pero el pedido se enviaba como invitado. Con `www.` delante, el resultado se invertía
 * para el mismo sitio.
 *
 * Ahora la bandera la decide el servidor, que es quien inicializa la tenancy por dominio.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    foreach ([
        'categories' => '2025_10_28_142911_create_categories',
        'tenant_settings' => '2025_10_28_144914_create_tenant_settings',
    ] as $table => $migration) {
        if (! Schema::hasTable($table)) {
            (require base_path("database/migrations/tenant/{$migration}.php"))->up();
        }
    }
});

test('el dominio central se anuncia como central', function () {
    $this->get('http://owomarket.local/')
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page->where('is_central', true));
});

// El caso que rompia la heurística: dos etiquetas, pero es una tienda.
test('una tienda con dominio propio de dos etiquetas NO se anuncia como central', function () {
    $tenantId = 't_dom_'.bin2hex(random_bytes(3));
    $tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Mi Tienda',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => 'mitienda.com',
    ]);

    $this->get('http://mitienda.com/')
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page->where('is_central', false));

    if (tenancy()->initialized) {
        tenancy()->end();
    }
});
