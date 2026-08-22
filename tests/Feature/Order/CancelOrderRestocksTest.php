<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Marketplace\Application\Service\StockReserver;
use Src\Order\Application\DTOs\CreateOrderData;
use Src\Order\Application\DTOs\OrderItemInputData;
use Src\Order\Application\UseCases\CancelOrderUseCase;
use Src\Order\Application\UseCases\CreateOrderUseCase;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Hallazgo N13: `StockReserver::release()` existía desde la Fase 1.3 y **no lo llamaba
 * nadie**, así que cancelar un pedido no devolvía una sola unidad al inventario.
 *
 * Quedaba pendiente «decidir en qué estados corresponde reponer», pero esa decisión ya la
 * toma el dominio: `OrderStatus::canBeCancelled()` sólo admite `pending`, `confirmed` y
 * `processing`. Un pedido enviado **no se puede cancelar**, así que si se llega a reponer
 * es porque la mercancía nunca salió del almacén.
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
        'customers' => '2025_10_28_144201_create_customers',
        'orders' => '2025_10_28_144320_create_orders',
        'order_items' => '2025_10_28_144403_create_order_items',
    ] as $table => $migration) {
        if (! Schema::hasTable($table)) {
            (require base_path("database/migrations/tenant/{$migration}.php"))->up();
        }
    }

    $tenantId = 't_cancel_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Cancelación',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => "{$tenantId}.localhost",
    ]);

    tenancy()->initialize($this->tenant);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('cancelar un pedido devuelve las unidades al inventario', function () {
    $product = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Camisa Blanca',
        'slug' => 'camisa-'.Str::random(4),
        'sku' => 'CAM-'.Str::random(4),
        'price' => 50.00,
        'quantity' => 10,
        'is_visible' => true,
        'track_quantity' => true,
    ]);

    $customerId = (string) Str::uuid();
    DB::table('customers')->insert([
        'id' => $customerId,
        'name' => 'Ana',
        'email' => 'ana_'.bin2hex(random_bytes(3)).'@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $order = app(CreateOrderUseCase::class)->execute(new CreateOrderData(
        customerId: $customerId,
        paymentMethod: 'bank_transfer',
        items: [new OrderItemInputData(
            productId: $product->id,
            productName: $product->name,
            sku: $product->sku,
            price: 50.00,
            quantity: 3,
        )],
    ));

    // Crear el pedido no descuenta stock por sí solo —eso lo hace el checkout—, así que
    // se reserva a mano para poder comprobar la reposición.
    app(StockReserver::class)->reserve($product->id, null, 3, $product->name);
    expect(Product::find($product->id)->quantity)->toBe(7);

    app(CancelOrderUseCase::class)->execute($order->id()->value(), 'El cliente cambió de opinión');

    expect(Product::find($product->id)->quantity)->toBe(10);
});
