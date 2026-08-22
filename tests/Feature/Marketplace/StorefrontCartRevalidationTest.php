<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Hallazgo G4: el carrito vivía en `localStorage` con el precio y el stock congelados el
 * día en que el comprador añadió cada producto, y no había ninguna revalidación. Desde la
 * Fase 0.4 el servidor resuelve los precios por su cuenta e ignora los del navegador, así
 * que el comprador descubría la diferencia al pagar.
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
        'brands' => '2025_10_28_143000_create_brands',
        'products' => '2025_10_28_143038_create_products',
        'product_images' => '2025_10_28_143251_create_product_images',
        'product_variants' => '2025_10_28_143954_create_product_variants',
    ] as $table => $migration) {
        if (! Schema::hasTable($table)) {
            (require base_path("database/migrations/tenant/{$migration}.php"))->up();
        }
    }

    $tenantId = 't_rev_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Revalidacion',
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

    $this->product = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Camisa Blanca',
        'slug' => 'camisa-blanca-'.Str::random(4),
        'sku' => 'CAM-'.Str::random(4),
        'price' => 50.00,
        'quantity' => 10,
        'is_visible' => true,
        'track_quantity' => true,
    ]);

    $this->revalidar = function (array $items) {
        return $this->postJson("http://{$this->domain}/cart/revalidate", ['items' => $items]);
    };
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('un carrito al día no reporta cambios', function () {
    ($this->revalidar)([[
        'product_id' => $this->product->id,
        'quantity' => 2,
        'price' => 50.00,
    ]])
        ->assertStatus(200)
        ->assertJsonPath('data.has_changes', false)
        ->assertJsonPath('data.lines.0.price', 50)
        ->assertJsonPath('data.lines.0.available', true);
});

// «El comerciante sube el precio de $50 a $80 y el comprador sigue viendo $50.»
test('un precio desactualizado se corrige y se reporta', function () {
    $this->product->update(['price' => 80.00]);

    ($this->revalidar)([[
        'product_id' => $this->product->id,
        'quantity' => 1,
        'price' => 50.00,
    ]])
        ->assertStatus(200)
        ->assertJsonPath('data.has_changes', true)
        ->assertJsonPath('data.lines.0.price', 80)
        ->assertJsonPath('data.lines.0.price_changed', true)
        ->assertJsonPath('data.lines.0.previous_price', 50);
});

test('la cantidad se recorta al stock que queda', function () {
    $this->product->update(['quantity' => 3]);

    ($this->revalidar)([[
        'product_id' => $this->product->id,
        'quantity' => 10,
        'price' => 50.00,
    ]])
        ->assertStatus(200)
        ->assertJsonPath('data.has_changes', true)
        ->assertJsonPath('data.lines.0.quantity', 3)
        ->assertJsonPath('data.lines.0.quantity_reduced', true);
});

test('un producto agotado se marca como no disponible', function () {
    $this->product->update(['quantity' => 0]);

    ($this->revalidar)([[
        'product_id' => $this->product->id,
        'quantity' => 1,
        'price' => 50.00,
    ]])
        ->assertStatus(200)
        ->assertJsonPath('data.lines.0.available', false);
});

// Una línea que ya no se puede servir no debe tumbar la petición entera: el comprador
// tiene que poder ver cuál es y quitarla.
test('un producto borrado se marca sin hacer fallar el resto del carrito', function () {
    $otro = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Pantalón',
        'slug' => 'pantalon-'.Str::random(4),
        'sku' => 'PAN-'.Str::random(4),
        'price' => 30.00,
        'quantity' => 5,
        'is_visible' => true,
    ]);
    $otro->delete();

    $response = ($this->revalidar)([
        ['product_id' => $this->product->id, 'quantity' => 1, 'price' => 50.00],
        ['product_id' => $otro->id, 'quantity' => 1, 'price' => 30.00],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.has_changes', true)
        ->assertJsonPath('data.lines.0.available', true)
        ->assertJsonPath('data.lines.1.available', false);
});

test('un producto oculto por el comerciante se marca como no disponible', function () {
    $this->product->update(['is_visible' => false]);

    ($this->revalidar)([[
        'product_id' => $this->product->id,
        'quantity' => 1,
        'price' => 50.00,
    ]])
        ->assertStatus(200)
        ->assertJsonPath('data.lines.0.available', false);
});
