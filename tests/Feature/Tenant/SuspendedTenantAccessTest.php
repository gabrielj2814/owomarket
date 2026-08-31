<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Fase 5 de `planes/por_hacer/PLAN_WALLET_Y_RETIROS.md`: cablear `suspended`.
 *
 * El estado existía —definido en `TenantStatus`, escrito por `TenantRepository::suspended()`,
 * invocado por dos casos de uso con sus endpoints— y **no lo leía nadie para impedir nada**.
 * Suspender una tienda era escribir una palabra en una columna: el comerciante entraba igual y
 * vendía igual, y lo único que cambiaba era un contador en el panel de admin.
 *
 * Es la palanca que hace cobrable la comisión del canal escaparate, donde el comprador paga
 * directo al comerciante y la plataforma no puede retener nada.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($b) => $b !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    foreach ([
        'categories' => '2025_10_28_142911_create_categories',
        'brands' => '2025_10_28_143000_create_brands',
        'products' => '2025_10_28_143038_create_products',
        'product_images' => '2025_10_28_143251_create_product_images',
        'product_variants' => '2025_10_28_143954_create_product_variants',
    ] as $table => $migration) {
        if (! Schema::hasTable($table)) {
            (require base_path("database/migrations/tenant/{$migration}.php"))->up();
        }
    }

    $tenantId = 't_susp_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Suspendida',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);

    tenancy()->initialize($this->tenant);
    $categoria = Category::create(['name' => 'Ropa', 'slug' => 'ropa-'.Str::random(4), 'is_active' => true]);
    $this->producto = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Camisa',
        'slug' => 'camisa-'.Str::random(4),
        'sku' => 'CAM-'.Str::random(4),
        'price' => 25.00,
        'quantity' => 10,
        'category_id' => $categoria->id,
        'is_visible' => true,
    ]);
    // Con sesion de la tienda: `auth` corre ANTES que `tenant_active`, y eso es lo correcto
    // --a un extraño sin sesion se le responde 401 sin contarle en que estado esta la tienda--,
    // asi que para llegar a la guarda de suspension hay que estar autenticado.
    $this->tenantUser = actingAsTenantOwner();

    tenancy()->end();
});

function suspender(ModelsTenant $tenant): void
{
    $tenant->update(['status' => 'suspended']);
}

it('el backoffice de una tienda suspendida deja de responder', function () {
    suspender($this->tenant);

    // 403 y no 200. Antes de cablearlo, esta misma peticion respondia con normalidad: la
    // suspension no impedia absolutamente nada.
    $respuesta = $this->getJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}");

    expect($respuesta->status())->toBe(403);
    expect($respuesta->json('message'))->toContain('suspendida');
});

it('el escaparate de una tienda suspendida sigue vendiendo', function () {
    // A propósito. La tienda sigue facturando y la deuda sigue creciendo; el comerciante es
    // quien no puede gestionarla. Cerrar el escaparate sería cobrarle la deuda al comprador.
    suspender($this->tenant);

    $this->getJson("http://{$this->domain}/api-tenant/customer/auth/session")
        ->assertStatus(200);
});

it('una tienda activa no se ve afectada', function () {
    // Que la guarda no deje pasar a nadie seria tan inutil como que dejara pasar a todos.
    $this->getJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}")
        ->assertStatus(200);
});

it('inactive no bloquea: no es una sanción', function () {
    // El enum admite `active`, `inactive` y `suspended`, y solo el ultimo castiga. Bloquear
    // `inactive` meteria en el mismo saco a una tienda que no ha terminado de darse de alta.
    $this->tenant->update(['status' => 'inactive']);

    $this->getJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}")
        ->assertStatus(200);
});

it('a un extraño sin sesión no se le cuenta que la tienda está suspendida', function () {
    // `auth` corre antes que `tenant_active` a proposito: el estado de una tienda no es
    // informacion publica, y un 403 explicando la suspension se la daria a cualquiera.
    suspender($this->tenant);

    auth()->guard('web')->logout();

    $this->getJson("http://{$this->domain}/api-tenant/product/{$this->producto->id}")
        ->assertStatus(401);
});
