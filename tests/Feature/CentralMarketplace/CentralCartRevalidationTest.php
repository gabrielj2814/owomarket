<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Hallazgo N31: la Fase 3.2 dio revalidación al carrito del storefront de cada tienda y
 * **dejó fuera el del marketplace central**, que seguía con el precio y la cantidad
 * congelados en `localStorage` el día en que se añadió cada producto.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($b) => $b !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    $this->tenant = ModelsTenant::create([
        'id' => 't_rev_'.bin2hex(random_bytes(3)),
        'name' => 'Tienda Central',
        'slug' => 't_rev_'.bin2hex(random_bytes(3)),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->central = CentralProduct::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'tenant_product_id' => (string) Str::uuid(),
        'name' => 'Camisa Blanca',
        'slug' => 'camisa-'.Str::random(4),
        'price' => 50.00,
        'quantity' => 10,
        'is_visible' => true,
    ]);

    $this->revalidar = function (array $overrides = []) {
        return $this->postJson('http://owomarket.local/api/central/marketplace/cart/revalidate', [
            'items' => [array_merge([
                'tenant_id' => $this->tenant->id,
                'product_id' => $this->central->tenant_product_id,
                'quantity' => 2,
                'price' => 50.00,
            ], $overrides)],
        ]);
    };
});

test('un carrito central al día no reporta cambios', function () {
    ($this->revalidar)()
        ->assertStatus(200)
        ->assertJsonPath('data.has_changes', false)
        ->assertJsonPath('data.lines.0.available', true);
});

test('un precio desactualizado del catálogo central se corrige y se reporta', function () {
    $this->central->update(['price' => 80.00]);

    ($this->revalidar)()
        ->assertStatus(200)
        ->assertJsonPath('data.has_changes', true)
        ->assertJsonPath('data.lines.0.price', 80)
        ->assertJsonPath('data.lines.0.price_changed', true);
});

test('la cantidad se recorta al stock del catálogo central', function () {
    $this->central->update(['quantity' => 1]);

    ($this->revalidar)(['quantity' => 5])
        ->assertStatus(200)
        ->assertJsonPath('data.lines.0.quantity', 1)
        ->assertJsonPath('data.lines.0.quantity_reduced', true);
});

test('un producto retirado del marketplace se marca como no disponible', function () {
    $this->central->update(['is_visible' => false]);

    ($this->revalidar)()
        ->assertStatus(200)
        ->assertJsonPath('data.lines.0.available', false);
});
