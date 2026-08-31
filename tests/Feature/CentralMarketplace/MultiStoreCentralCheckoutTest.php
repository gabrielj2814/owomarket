<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\CentralMarketplace\Application\UseCases\DispatchCentralOrderToTenantsUseCase;
use Src\CentralMarketplace\Infrastructure\Eloquent\Models\CentralOrderDispatch;
use Src\CentralMarketplace\Infrastructure\Jobs\DispatchCentralOrderJob;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Monetization\Application\UseCases\CalculateAndRecordOrderCommissionUseCase;
use Src\Monetization\Application\UseCases\ListSubscriptionPlansUseCase;
use Src\Monetization\Application\UseCases\SubscribeTenantToPlanUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Product\Infrastructure\Eloquent\Models\ProductVariant;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    // Ensure tenant tables exist
    if (! Schema::hasTable('customers')) {
        (require base_path('database/migrations/tenant/2025_10_28_144201_create_customers.php'))->up();
    }
    if (! Schema::hasColumn('customers', 'central_uuid')) {
        (require base_path('database/migrations/tenant/2026_08_19_000002_add_central_uuid_to_customers.php'))->up();
    }
    if (! Schema::hasTable('categories')) {
        (require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php'))->up();
    }
    if (! Schema::hasTable('brands')) {
        (require base_path('database/migrations/tenant/2025_10_28_143000_create_brands.php'))->up();
    }
    if (! Schema::hasTable('products')) {
        (require base_path('database/migrations/tenant/2025_10_28_143038_create_products.php'))->up();
    }
    if (! Schema::hasTable('product_variants')) {
        (require base_path('database/migrations/tenant/2025_10_28_143954_create_product_variants.php'))->up();
    }
    // N34/N28: la tienda necesita sus tarifas de envio, sus impuestos y sus cupones.
    if (! Schema::hasTable('shipping_zones')) {
        (require base_path('database/migrations/tenant/2025_10_28_145209_create_shipping_zones.php'))->up();
    }
    if (! Schema::hasTable('shipping_rates')) {
        (require base_path('database/migrations/tenant/2025_10_28_145238_create_shipping_rates.php'))->up();
    }
    if (! Schema::hasTable('tax_rates')) {
        (require base_path('database/migrations/tenant/2025_10_28_145148_create_tax_rates.php'))->up();
    }
    if (! Schema::hasTable('coupons')) {
        (require base_path('database/migrations/tenant/2025_10_28_144655_create_coupons.php'))->up();
    }
    if (! Schema::hasTable('orders')) {
        (require base_path('database/migrations/tenant/2025_10_28_144320_create_orders.php'))->up();
    }
    if (! Schema::hasTable('order_items')) {
        (require base_path('database/migrations/tenant/2025_10_28_144403_create_order_items.php'))->up();
    }
    // Fase 4: la columna donde el pedido de cada tienda hereda la tasa del pedido central.
    if (Schema::hasColumn('orders', 'id') && ! Schema::hasColumn('orders', 'exchange_rate')) {
        (require base_path('database/migrations/tenant/2026_08_30_130000_add_exchange_rate_to_orders_table.php'))->up();
    }
    if (! Schema::hasTable('payments')) {
        (require base_path('database/migrations/tenant/2025_10_28_144517_create_payments.php'))->up();
    }
    if (! Schema::hasTable('tenant_settings')) {
        (require base_path('database/migrations/tenant/2025_10_28_144914_create_tenant_settings.php'))->up();
    }
    if (! Schema::hasColumn('products', 'category_id')) {
        (require base_path('database/migrations/tenant/2026_08_18_000004_add_category_and_brand_to_products_table.php'))->up();
    }

    // Ensure central tables exist
    if (! Schema::hasTable('subscription_plans')) {
        (require base_path('database/migrations/2026_08_19_000003_create_monetization_tables.php'))->up();
    }
    if (! Schema::hasTable('central_customers')) {
        (require base_path('database/migrations/2026_08_19_000001_create_central_customers_tables.php'))->up();
    }
    if (! Schema::hasTable('central_orders')) {
        (require base_path('database/migrations/2026_08_19_000004_create_central_orders_tables.php'))->up();
    }
    if (! Schema::hasTable('central_products')) {
        (require base_path('database/migrations/2026_08_19_000007_create_central_products_table.php'))->up();
    }
    // Fase 1.1 (hallazgo C2): idempotencia del checkout y del despacho.
    if (! Schema::hasTable('central_order_dispatches')) {
        (require base_path('database/migrations/2026_08_21_100000_add_idempotency_to_central_orders.php'))->up();
    }

    app(ListSubscriptionPlansUseCase::class)->execute();

    // 1. Create Tenant A (Deportes)
    $tenantAId = 't_dep_'.bin2hex(random_bytes(3));
    $this->tenantA = ModelsTenant::create([
        'id' => $tenantAId,
        'name' => 'Mega Deportes Store',
        'slug' => $tenantAId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->tenantA->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => "{$tenantAId}.localhost",
    ]);

    // 2. Create Tenant B (Tecnologia)
    $tenantBId = 't_tec_'.bin2hex(random_bytes(3));
    $this->tenantB = ModelsTenant::create([
        'id' => $tenantBId,
        'name' => 'Cyber Tech Store',
        'slug' => $tenantBId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->tenantB->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => "{$tenantBId}.localhost",
    ]);

    // Setup products in Tenant A
    tenancy()->initialize($this->tenantA);
    $catA = Category::create(['name' => 'Balones', 'slug' => 'balones-'.Str::random(4), 'is_active' => true]);
    $this->productA = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Balón de Fútbol Pro',
        'slug' => 'balon-futbol-pro-'.Str::random(4),
        'sku' => 'BALL-01',
        'price' => 50.00,
        'quantity' => 10,
        'category_id' => $catA->id,
        'is_visible' => true,
    ]);
    tenancy()->end();

    // Setup products in Tenant B
    tenancy()->initialize($this->tenantB);
    $catB = Category::create(['name' => 'Audífonos', 'slug' => 'audifonos-'.Str::random(4), 'is_active' => true]);
    $this->productB = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Audífonos Bluetooth Noise Cancelling',
        'slug' => 'audifonos-bt-'.Str::random(4),
        'sku' => 'TECH-EAR-01',
        'price' => 100.00,
        'quantity' => 20,
        'category_id' => $catB->id,
        'is_visible' => true,
    ]);
    tenancy()->end();

    // Hallazgo B1: el checkout central ya no acepta el precio del navegador;
    // lo resuelve contra `central_products`, así que ambos productos deben
    // estar publicados en el catálogo central para poder comprarse.
    CentralProduct::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenantA->id,
        'tenant_product_id' => $this->productA->id,
        'name' => $this->productA->name,
        'slug' => $this->productA->slug,
        'sku' => $this->productA->sku,
        'price' => 50.00,
        'quantity' => 10,
        'is_visible' => true,
    ]);

    CentralProduct::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenantB->id,
        'tenant_product_id' => $this->productB->id,
        'name' => $this->productB->name,
        'slug' => $this->productB->slug,
        'sku' => $this->productB->sku,
        'price' => 100.00,
        'quantity' => 20,
        'is_visible' => true,
    ]);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('Multi-Store Central Checkout creates unified master order and automatically dispatches local orders to both tenants with commissions', function () {
    // Set Tenant A on Pro Plan (3.5% fee)
    app(SubscribeTenantToPlanUseCase::class)->execute($this->tenantA->id, 'pro');

    $refNumber = 'PM-MASTER-'.random_int(100000, 999999);

    $payload = [
        'customer' => [
            'name' => 'Carlos Marketplace',
            'email' => 'carlos.mkt@example.com',
            'phone' => '+584121234567',
            'document_id' => 'V-18999888',
        ],
        'shipping_address' => [
            'address' => 'Av. Francisco Solano, Edif. Centro, Piso 1',
            'city' => 'Caracas',
            'state' => 'Miranda',
            'zip' => '1050',
            'notes' => 'Tocar timbre 1B',
        ],
        'payment_method' => 'pago_movil',
        'payment_details' => [
            'bank_origin' => 'Banco Provincial',
            'phone_origin' => '04121234567',
            'reference_number' => $refNumber,
        ],
        'shipping_amount' => 10.00,
        'discount_amount' => 0.00,
        'items' => [
            [
                'tenant_id' => $this->tenantA->id,
                'product_id' => $this->productA->id,
                'product_name' => $this->productA->name,
                'sku' => $this->productA->sku,
                'price' => 50.00,
                'quantity' => 2, // 100.00 from Tenant A
            ],
            [
                'tenant_id' => $this->tenantB->id,
                'product_id' => $this->productB->id,
                'product_name' => $this->productB->name,
                'sku' => $this->productB->sku,
                'price' => 100.00,
                'quantity' => 1, // 100.00 from Tenant B
            ],
        ],
    ];

    // 1. Execute Central Unified Checkout API
    $response = $this->postJson('/api/central/marketplace/checkout/create-order', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('code', 201);

    $centralOrderId = $response->json('data.order_id');
    expect($centralOrderId)->not->toBeNull();

    // 2. Verify Central Master Order
    $masterOrder = CentralOrder::with('items')->find($centralOrderId);
    expect($masterOrder)->not->toBeNull();
    expect($masterOrder->subtotal)->toBe(200.00);
    // Hallazgo N34: `shipping_amount` ya NO se toma del navegador. Cada tienda calcula
    // el suyo con sus propias tarifas, y estas tiendas de prueba no tienen ninguna
    // configurada, asi que el envio es 0 y el total es el subtotal.
    expect($masterOrder->total)->toBe(200.00);
    expect($masterOrder->items->count())->toBe(2);

    // 3. Verify Local Orders in Tenant A and Tenant B
    $itemA = $masterOrder->items->firstWhere('tenant_id', $this->tenantA->id);
    $itemB = $masterOrder->items->firstWhere('tenant_id', $this->tenantB->id);

    expect($itemA->tenant_order_id)->not->toBeNull();
    expect($itemB->tenant_order_id)->not->toBeNull();

    // Verify Tenant A local order in DB
    // Hallazgo D1: el envío de $10 se reparte entre las dos tiendas (mitad y
    // mitad, porque aportan $100 cada una), en vez de perderse. Antes cada
    // pedido de tienda quedaba en $100 y la suma no cuadraba con lo que
    // pagó el cliente.
    tenancy()->initialize($this->tenantA);
    $localOrderA = DB::table('orders')->where('id', $itemA->tenant_order_id)->first();
    expect($localOrderA)->not->toBeNull();
    expect((float) $localOrderA->total)->toBe(100.00);
    $paymentA = DB::table('payments')->where('order_id', $itemA->tenant_order_id)->first();
    expect($paymentA)->not->toBeNull();
    expect((float) $paymentA->amount)->toBe(100.00);
    expect($paymentA->payment_gateway)->toBe('pago_movil');
    tenancy()->end();

    // Verify Tenant B local order in DB
    tenancy()->initialize($this->tenantB);
    $localOrderB = DB::table('orders')->where('id', $itemB->tenant_order_id)->first();
    expect($localOrderB)->not->toBeNull();
    expect((float) $localOrderB->total)->toBe(100.00);
    $paymentB = DB::table('payments')->where('order_id', $itemB->tenant_order_id)->first();
    expect($paymentB)->not->toBeNull();
    expect((float) $paymentB->amount)->toBe(100.00);
    tenancy()->end();

    // La suma de los pedidos de tienda cuadra ahora con el total central.
    expect((float) $localOrderA->total + (float) $localOrderB->total)
        ->toBe((float) $masterOrder->total);

    // 4. Verify Platform Commissions in Central DB
    $commissions = PlatformCommission::where('order_id', $itemA->tenant_order_id)
        ->orWhere('order_id', $itemB->tenant_order_id)
        ->get();

    expect($commissions->count())->toBe(2);

    // Hallazgo D1: la comisión se cobra sobre la mercancía neta de descuento,
    // SIN incluir el envío. Aquí no hay descuento, así que la base sigue siendo
    // $100 por tienda y los importes no cambian respecto al comportamiento
    // anterior — pero el envío prorrateado ya NO infla la base.
    $commA = $commissions->firstWhere('tenant_id', $this->tenantA->id);
    expect($commA)->not->toBeNull();
    expect($commA->commission_rate)->toBe(3.50); // Pro plan
    expect($commA->commission_amount)->toBe(3.50); // 100 * 3.5%
    expect((float) $commA->order_total)->toBe(100.00); // mercancia neta de descuento

    $commB = $commissions->firstWhere('tenant_id', $this->tenantB->id);
    expect($commB)->not->toBeNull();
    expect($commB->commission_rate)->toBe(8.00); // Default global plan
    expect($commB->commission_amount)->toBe(8.00); // 100 * 8%

    // 5. Verify Unified Confirmation API
    $confResponse = $this->getJson("/api/central/marketplace/order/{$centralOrderId}/confirmation");
    $confResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.stores_count', 2)
        ->assertJsonPath('data.total', 200);

    $breakdowns = $confResponse->json('data.stores_breakdown');
    expect(count($breakdowns))->toBe(2);
});

test('El checkout central ignora el precio que envía el navegador (hallazgo B1)', function () {
    $payload = [
        'customer' => [
            'name' => 'Comprador Malicioso',
            'email' => 'malicioso@example.com',
        ],
        'shipping_address' => [
            'address' => 'Calle Falsa 123',
            'city' => 'Caracas',
        ],
        'payment_method' => 'pago_movil',
        'shipping_amount' => 0.00,
        'items' => [
            [
                'tenant_id' => $this->tenantA->id,
                'product_id' => $this->productA->id,
                // El producto vale 50.00 en el catálogo central.
                'product_name' => 'Balón regalado',
                'sku' => 'FAKE',
                'price' => 0.01,
                'quantity' => 2,
            ],
        ],
    ];

    $response = $this->postJson('/api/central/marketplace/checkout/create-order', $payload);
    $response->assertStatus(201);

    $order = CentralOrder::with('items')->find($response->json('data.order_id'));

    // 2 x 50.00 = 100.00, no 2 x 0.01 = 0.02.
    expect($order->subtotal)->toBe(100.00);
    expect($order->total)->toBe(100.00);

    $item = $order->items->first();
    expect($item->price)->toBe(50.00);
    expect($item->total)->toBe(100.00);
    // El nombre y el SKU también salen del catálogo, no del navegador.
    expect($item->product_name)->toBe($this->productA->name);
    expect($item->sku)->toBe($this->productA->sku);
});

test('El checkout central rechaza productos despublicados del catálogo', function () {
    CentralProduct::where('tenant_id', $this->tenantA->id)->update(['is_visible' => false]);

    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente', 'email' => 'cliente@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            [
                'tenant_id' => $this->tenantA->id,
                'product_id' => $this->productA->id,
                'price' => 50.00,
                'quantity' => 1,
            ],
        ],
    ]);

    expect($response->getStatusCode())->toBeGreaterThanOrEqual(400);
    expect(CentralOrder::count())->toBe(0);
});

/**
 * Hallazgos N34 y N28, que reemplazan al escenario original de D1.
 *
 * D1 comprobaba que el envío y el descuento **globales** se prorratearan entre las tiendas
 * en vez de perderse. Desde el 22/08/2026 ya no hay envío ni descuento globales: cada
 * tienda calcula el suyo con sus propias tarifas y sus propios cupones, y el pedido central
 * suma. La preocupación de D1 sigue viva —que ni un céntimo se pierda ni se invente— y es
 * lo que se comprueba abajo.
 *
 * El prorrateo se conserva en el código como respaldo para los pedidos creados antes del
 * cambio, que no llevan desglose por tienda.
 */
test('cada tienda aporta su propio envío, impuesto y cupón al pedido central (N34, N28)', function () {
    // Tarifa de envío de $8 y un cupón del 10%.
    //
    // La suite corre con `DatabaseTenancyBootstrapper` desactivado, así que **todas las
    // tiendas comparten la misma base SQLite** y ambas ven la misma tarifa. En producción
    // cada una tiene la suya. Lo que sí se puede comprobar aquí, y es lo que importa, es
    // que el envío sale de las tarifas de la tienda y NO del navegador, y que el cupón
    // sólo descuenta a la tienda cuyo código se envió.
    tenancy()->initialize($this->tenantA);

    $zoneId = (string) Str::uuid();
    DB::table('shipping_zones')->insert([
        'id' => $zoneId,
        'name' => 'Nacional',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('shipping_rates')->insert([
        'id' => (string) Str::uuid(),
        'shipping_zone_id' => $zoneId,
        'name' => 'Estándar',
        'type' => 'flat',
        'cost' => 8.00,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('coupons')->insert([
        'id' => (string) Str::uuid(),
        'code' => 'DIEZ',
        'type' => 'percentage',
        'value' => 10.00,
        'valid_from' => now()->subMonth()->toDateString(),
        'valid_to' => now()->addMonth()->toDateString(),
        'is_active' => true,
        'used_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    tenancy()->end();

    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Cargos', 'email' => 'cargos@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        // El navegador ya no decide estos importes; se ignoran a propósito.
        'shipping_amount' => 999.00,
        'discount_amount' => 999.00,
        'coupons' => [$this->tenantA->id => 'DIEZ'],
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
            ['tenant_id' => $this->tenantB->id, 'product_id' => $this->productB->id, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(201);

    // A = $50 + $8 de envío − $5 de cupón = $53. B = $100 + $8 = $108.
    // Total = 150 + 16 − 5 = $161. Y los $999 del navegador se ignoran por completo.
    $order = CentralOrder::with('items')->find($response->json('data.order_id'));
    expect($order->subtotal)->toBe(150.00)
        ->and((float) $order->shipping_amount)->toBe(16.00)
        ->and((float) $order->discount_amount)->toBe(5.00)
        ->and($order->total)->toBe(161.00);

    $itemA = $order->items->firstWhere('tenant_id', $this->tenantA->id);
    $itemB = $order->items->firstWhere('tenant_id', $this->tenantB->id);

    tenancy()->initialize($this->tenantA);
    $localA = DB::table('orders')->where('id', $itemA->tenant_order_id)->first();
    // El envío y el descuento de A son SUYOS, no una parte prorrateada de un total.
    expect((float) $localA->shipping_amount)->toBe(8.00)
        ->and((float) $localA->discount_amount)->toBe(5.00)
        ->and((float) $localA->total)->toBe(53.00);
    // Y el cupón quedó consumido (hallazgo N28).
    expect((int) DB::table('coupons')->where('code', 'DIEZ')->value('used_count'))->toBe(1);
    tenancy()->end();

    tenancy()->initialize($this->tenantB);
    $localB = DB::table('orders')->where('id', $itemB->tenant_order_id)->first();
    // A B no se le carga NADA del descuento de A: el cupón es de quien lo emitió.
    expect((float) $localB->discount_amount)->toBe(0.00)
        ->and((float) $localB->total)->toBe(108.00);
    tenancy()->end();

    // Lo esencial de D1 sigue en pie: la suma de los pedidos de tienda es EXACTAMENTE lo
    // que pagó el cliente.
    expect(round((float) $localA->total + (float) $localB->total, 2))->toBe(161.00);

    // Y la comisión, sobre la mercancía neta de descuento y sin envío: A → 50 − 5 = $45.
    $commA = PlatformCommission::where('order_id', $itemA->tenant_order_id)->first();
    expect((float) $commA->order_total)->toBe(45.00);
});

test('Reenviar el checkout con la misma clave de idempotencia no duplica el pedido (hallazgo C2)', function () {
    $payload = [
        'customer' => ['name' => 'Cliente Reintento', 'email' => 'reintento@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'shipping_amount' => 0.00,
        'idempotency_key' => 'checkout-intento-unico-123',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
        ],
    ];

    $first = $this->postJson('/api/central/marketplace/checkout/create-order', $payload);
    $first->assertStatus(201);
    $firstOrderId = $first->json('data.order_id');

    // El cliente reintenta (doble clic, o error de red tras haberse creado).
    $second = $this->postJson('/api/central/marketplace/checkout/create-order', $payload);
    $second->assertStatus(201);

    // Mismo pedido, no uno nuevo.
    expect($second->json('data.order_id'))->toBe($firstOrderId);
    expect(CentralOrder::count())->toBe(1);

    // Y sobre todo: una sola comisión. Antes el reintento creaba un segundo
    // CentralOrder y la tienda acababa cobrada dos veces por una compra.
    expect(PlatformCommission::where('tenant_id', $this->tenantA->id)->count())->toBe(1);

    // El pedido llegó a la tienda una sola vez.
    tenancy()->initialize($this->tenantA);
    expect(DB::table('orders')->count())->toBe(1);
    tenancy()->end();
});

test('El despacho no repite el pedido en una tienda ya despachada (hallazgo C2)', function () {
    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Despacho', 'email' => 'despacho@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
        ],
    ]);
    $response->assertStatus(201);

    $order = CentralOrder::with('items')->find($response->json('data.order_id'));

    // Se fuerza un segundo despacho del MISMO pedido central.
    app(\Src\CentralMarketplace\Application\UseCases\DispatchCentralOrderToTenantsUseCase::class)
        ->execute($order);

    tenancy()->initialize($this->tenantA);
    expect(DB::table('orders')->count())->toBe(1);
    expect(DB::table('payments')->count())->toBe(1);
    tenancy()->end();

    expect(PlatformCommission::where('tenant_id', $this->tenantA->id)->count())->toBe(1);
});

/**
 * Hallazgo N14: **el checkout central no reservaba stock en absoluto.**
 * `DispatchCentralOrderToTenantsUseCase` creaba pedidos en cada tienda sin tocar el
 * inventario, así que todo el trabajo de bloqueos de la Fase 1.3 sólo protegía el
 * storefront de cada tienda: por el marketplace se podían vender unidades que no existían,
 * y el stock nunca bajaba con las ventas.
 */
test('el checkout central descuenta el stock de cada tienda (hallazgo N14)', function () {
    $payload = [
        'customer' => ['name' => 'Ana', 'email' => 'ana.stock@example.com'],
        'shipping_address' => ['address' => 'Av. 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'payment_details' => ['reference_number' => 'PM-'.random_int(100000, 999999)],
        'items' => [
            [
                'tenant_id' => $this->tenantA->id,
                'product_id' => $this->productA->id,
                'quantity' => 2,
                'price' => 50.00,
            ],
            [
                'tenant_id' => $this->tenantB->id,
                'product_id' => $this->productB->id,
                'quantity' => 3,
                'price' => 25.00,
            ],
        ],
    ];

    $this->postJson('http://owomarket.local/api/central/marketplace/checkout/create-order', $payload)
        ->assertStatus(201);

    tenancy()->initialize($this->tenantA);
    expect(Product::find($this->productA->id)->quantity)->toBe(8); // 10 - 2
    tenancy()->end();

    tenancy()->initialize($this->tenantB);
    expect(Product::find($this->productB->id)->quantity)->toBe(17); // 20 - 3
    tenancy()->end();
});

test('el checkout central rechaza el pedido si una tienda no tiene existencias', function () {
    tenancy()->initialize($this->tenantA);
    Product::where('id', $this->productA->id)->first()->update(['quantity' => 1]);
    tenancy()->end();

    $payload = [
        'customer' => ['name' => 'Ana', 'email' => 'ana.sinstock@example.com'],
        'shipping_address' => ['address' => 'Av. 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'payment_details' => ['reference_number' => 'PM-'.random_int(100000, 999999)],
        'items' => [[
            'tenant_id' => $this->tenantA->id,
            'product_id' => $this->productA->id,
            'quantity' => 5,
            'price' => 50.00,
        ]],
    ];

    $this->postJson('http://owomarket.local/api/central/marketplace/checkout/create-order', $payload);

    // Lo que no puede pasar es que el stock quede negativo ni que se cree el pedido
    // de tienda como si hubiera existencias.
    tenancy()->initialize($this->tenantA);
    expect(Product::find($this->productA->id)->quantity)->toBe(1);
    tenancy()->end();
});

// N34: la pantalla mostraba el subtotal puro como total. El presupuesto devuelve lo mismo
// que calculará el servidor al crear el pedido, para que no tenga que inventarlo.
test('el presupuesto del checkout central devuelve el total real', function () {
    tenancy()->initialize($this->tenantA);
    $zoneId = (string) Str::uuid();
    DB::table('shipping_zones')->insert([
        'id' => $zoneId, 'name' => 'Nacional', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('shipping_rates')->insert([
        'id' => (string) Str::uuid(), 'shipping_zone_id' => $zoneId, 'name' => 'Estándar',
        'type' => 'flat', 'cost' => 8.00, 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    tenancy()->end();

    $this->postJson('/api/central/marketplace/checkout/quote', [
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
        ],
        'shipping_address' => ['city' => 'Caracas'],
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.subtotal', 50)
        ->assertJsonPath('data.shipping', 8)
        ->assertJsonPath('data.total', 58);
});

test('el presupuesto informa de un cupón inválido sin romperse', function () {
    $response = $this->postJson('/api/central/marketplace/checkout/quote', [
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
        ],
        'shipping_address' => ['city' => 'Caracas'],
        'coupons' => [$this->tenantA->id => 'NOEXISTE'],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.discount', 0)
        ->assertJsonPath("data.by_tenant.{$this->tenantA->id}.coupon_code", null);

    expect($response->json("data.by_tenant.{$this->tenantA->id}.coupon_error"))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Hallazgo N17 — el despacho se reintenta
|--------------------------------------------------------------------------
|
| Los despachos fallidos se quedaban en `status = 'failed'` y nada los volvía a
| intentar: un pedido cobrado podía no llegar nunca a su tienda. Y el despacho iba
| dentro de la petición del checkout, así que la respuesta al comprador quedaba a
| merced de la tienda más lenta.
*/

test('el checkout encola el despacho en vez de hacerlo dentro de la petición (N17)', function () {
    Queue::fake();

    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Cola', 'email' => 'cola@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(201);

    // El comprador ya tiene su número de pedido; la propagación a las tiendas va aparte.
    Queue::assertPushed(DispatchCentralOrderJob::class);
});

test('un despacho fallido se reclama en el siguiente intento (N17)', function () {
    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Reintento', 'email' => 'reintento@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
        ],
    ]);
    $response->assertStatus(201);

    $order = CentralOrder::with(['items', 'customer'])->find($response->json('data.order_id'));

    // Se simula el estado en el que quedaba un despacho roto: fila en 'failed' y sin
    // pedido en la tienda. Antes de N17 esa fila bloqueaba la tienda PARA SIEMPRE, porque
    // `reserveDispatch` devolvía null en cuanto existía cualquier fila.
    tenancy()->initialize($this->tenantA);
    // En orden hijo -> padre: `orders` tiene claves foraneas apuntandole.
    DB::table('payments')->delete();
    DB::table('order_items')->delete();
    DB::table('orders')->delete();
    tenancy()->end();

    CentralOrderDispatch::where('central_order_id', $order->id)
        ->where('tenant_id', $this->tenantA->id)
        ->update(['status' => 'failed', 'tenant_order_id' => null, 'error_message' => 'BD de la tienda caída']);

    app(DispatchCentralOrderToTenantsUseCase::class)->execute($order->fresh(['items', 'customer']));

    // El reintento sí alcanza a la tienda que había fallado.
    tenancy()->initialize($this->tenantA);
    expect(DB::table('orders')->count())->toBe(1);
    tenancy()->end();

    $dispatch = CentralOrderDispatch::where('central_order_id', $order->id)
        ->where('tenant_id', $this->tenantA->id)
        ->first();

    expect($dispatch->status)->toBe('dispatched')
        ->and($dispatch->attempts)->toBe(1)
        ->and($dispatch->error_message)->toBeNull();
});

test('una tienda que agota los intentos deja de reintentarse (N17)', function () {
    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Tope', 'email' => 'tope@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
        ],
    ]);
    $response->assertStatus(201);

    $order = CentralOrder::with(['items', 'customer'])->find($response->json('data.order_id'));

    tenancy()->initialize($this->tenantA);
    DB::table('payments')->delete();
    DB::table('order_items')->delete();
    DB::table('orders')->delete();
    tenancy()->end();

    // Fila fallida que ya gastó el tope: seguir machacando una tienda rota sólo llena la
    // cola de trabajo inútil, así que se queda como está hasta que alguien intervenga.
    CentralOrderDispatch::where('central_order_id', $order->id)
        ->where('tenant_id', $this->tenantA->id)
        ->update([
            'status' => 'failed',
            'tenant_order_id' => null,
            'attempts' => DispatchCentralOrderToTenantsUseCase::MAX_DISPATCH_ATTEMPTS,
        ]);

    app(DispatchCentralOrderToTenantsUseCase::class)->execute($order->fresh(['items', 'customer']));

    tenancy()->initialize($this->tenantA);
    expect(DB::table('orders')->count())->toBe(0);
    tenancy()->end();

    expect(CentralOrderDispatch::where('central_order_id', $order->id)->first()->status)->toBe('failed');
});

/*
|--------------------------------------------------------------------------
| Hallazgo N36 — el marketplace central vende por variante
|--------------------------------------------------------------------------
|
| `central_order_items` no guardaba la variante, asi que pasaban dos cosas a la vez: el
| comprador no podia elegir talla ni color, y la reserva de stock descontaba del producto
| PADRE, cuyo `quantity` no lo mantiene nadie cuando hay variantes —`StockReserver` solo
| toca la variante cuando se le pasa una—. Vender por el marketplace descuadraba los dos.
*/

/** Crea en la tienda A un producto con dos variantes y lo publica en el catalogo central. */
function productoConVariantes(object $test): array
{
    tenancy()->initialize($test->tenantA);

    $producto = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Camiseta Tecnica',
        'slug' => 'camiseta-tecnica-'.Str::random(4),
        'sku' => 'CAM-TEC',
        'price' => 20.00,
        // Deliberadamente distinto de la suma de las variantes: es el numero que nadie
        // mantiene, y el que se descontaba antes de N36.
        'quantity' => 99,
        'is_visible' => true,
    ]);

    $tallaS = ProductVariant::create([
        'id' => (string) Str::uuid(),
        'product_id' => $producto->id,
        'sku' => 'CAM-TEC-S',
        'price' => 20.00,
        'quantity' => 5,
        'attributes' => ['Talla' => 'S'],
    ]);

    $tallaM = ProductVariant::create([
        'id' => (string) Str::uuid(),
        'product_id' => $producto->id,
        'sku' => 'CAM-TEC-M',
        'price' => 25.00,
        'quantity' => 3,
        'attributes' => ['Talla' => 'M'],
    ]);

    tenancy()->end();

    CentralProduct::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $test->tenantA->id,
        'tenant_product_id' => $producto->id,
        'name' => $producto->name,
        'slug' => $producto->slug,
        'sku' => $producto->sku,
        'price' => 20.00,
        'quantity' => 99,
        'is_visible' => true,
        'variants' => [
            ['id' => $tallaS->id, 'sku' => 'CAM-TEC-S', 'price' => 20.00, 'quantity' => 5, 'attributes' => ['Talla' => 'S']],
            ['id' => $tallaM->id, 'sku' => 'CAM-TEC-M', 'price' => 25.00, 'quantity' => 3, 'attributes' => ['Talla' => 'M']],
        ],
    ]);

    return ['producto' => $producto, 'S' => $tallaS, 'M' => $tallaM];
}

test('comprar una variante descuenta SU stock y no el del producto padre (N36)', function () {
    $v = productoConVariantes($this);

    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Variante', 'email' => 'variante@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $v['producto']->id, 'variant_id' => $v['M']->id, 'quantity' => 2],
        ],
    ]);

    $response->assertStatus(201);

    tenancy()->initialize($this->tenantA);
    $padre = Product::find($v['producto']->id);
    $tallaS = ProductVariant::find($v['S']->id);
    $tallaM = ProductVariant::find($v['M']->id);
    tenancy()->end();

    // La talla comprada baja...
    expect((int) $tallaM->quantity)->toBe(1);
    // ...y ni la otra talla ni el padre se tocan. Antes de N36 bajaba el padre.
    expect((int) $tallaS->quantity)->toBe(5);
    expect((int) $padre->quantity)->toBe(99);
});

test('el precio cobrado es el de la variante, no el del padre (N36)', function () {
    $v = productoConVariantes($this);

    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Precio', 'email' => 'precio@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $v['producto']->id, 'variant_id' => $v['M']->id, 'quantity' => 2],
        ],
    ]);
    $response->assertStatus(201);

    $item = CentralOrderItem::where('central_order_id', $response->json('data.order_id'))->first();

    // La M cuesta 25, el padre 20. Cobrar el del padre regalaba 5 por unidad.
    expect((float) $item->price)->toBe(25.00)
        ->and((string) $item->variant_id)->toBe((string) $v['M']->id)
        ->and($item->sku)->toBe('CAM-TEC-M');
});

test('la variante llega al pedido de la tienda, que es lo que el comerciante prepara (N36)', function () {
    $v = productoConVariantes($this);

    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Envio', 'email' => 'envio@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $v['producto']->id, 'variant_id' => $v['S']->id, 'quantity' => 1],
        ],
    ]);
    $response->assertStatus(201);

    tenancy()->initialize($this->tenantA);
    $linea = DB::table('order_items')->first();
    tenancy()->end();

    expect((string) $linea->product_variant_id)->toBe((string) $v['S']->id);
});

test('no se puede comprar un producto con variantes sin elegir una (N36)', function () {
    $v = productoConVariantes($this);

    // Antes esto vendia el padre en silencio: el comerciante recibia el pedido sin saber
    // que talla enviar, y el stock salia del numero equivocado.
    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Sin Opcion', 'email' => 'sinopcion@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $v['producto']->id, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('elegir una opcion');
});

test('el checkout rechaza el pedido si la variante no tiene existencias (N36)', function () {
    $v = productoConVariantes($this);

    // La M tiene 3. El padre tiene 99, asi que sin N36 esto pasaba y vendia aire.
    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Sin Stock', 'email' => 'sinstock@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $v['producto']->id, 'variant_id' => $v['M']->id, 'quantity' => 10],
        ],
    ]);

    expect($response->status())->toBeGreaterThanOrEqual(400);

    tenancy()->initialize($this->tenantA);
    expect((int) ProductVariant::find($v['M']->id)->quantity)->toBe(3);
    tenancy()->end();
});

/*
|--------------------------------------------------------------------------
| Hallazgo Auditoria #1 — la comision se relaciona con su pedido central
|--------------------------------------------------------------------------
|
| `order_id` guarda el UUID del pedido dentro de la base del INQUILINO, pero las
| relaciones Eloquent lo declaraban contra `central_orders`. Como esos identificadores
| viven en bases distintas, nunca coincidian: `$centralOrder->commissions` devolvia
| SIEMPRE una coleccion vacia, y sin lanzar ningun error.
*/

test('la comision de un pedido central se puede recuperar desde el pedido (#1)', function () {
    $v = ['tenant' => $this->tenantA];

    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Comision', 'email' => 'comision@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
        ],
    ]);
    $response->assertStatus(201);

    $centralOrder = CentralOrder::find($response->json('data.order_id'));

    // Esto era lo roto: la coleccion salia vacia aunque la comision existiera.
    expect($centralOrder->commissions)->toHaveCount(1);

    $comision = $centralOrder->commissions->first();

    // Y la relacion inversa tambien resuelve.
    expect($comision->order)->not->toBeNull()
        ->and((string) $comision->order->id)->toBe((string) $centralOrder->id);
});

test('order_id sigue apuntando al pedido de la tienda, no al central (#1)', function () {
    // El arreglo NO renombra `order_id`: los informes del comerciante lo necesitan
    // apuntando a su propio pedido. Lo que se anade es una columna aparte.
    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Doble Id', 'email' => 'dobleid@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
        ],
    ]);
    $response->assertStatus(201);

    $centralOrder = CentralOrder::find($response->json('data.order_id'));
    $comision = PlatformCommission::where('central_order_id', $centralOrder->id)->first();

    expect($comision)->not->toBeNull()
        ->and((string) $comision->central_order_id)->toBe((string) $centralOrder->id)
        ->and((string) $comision->order_id)->not->toBe((string) $centralOrder->id);

    // El `order_id` es el pedido que se creo dentro de la tienda.
    tenancy()->initialize($this->tenantA);
    $pedidoDeTienda = DB::table('orders')->first();
    tenancy()->end();

    expect((string) $comision->order_id)->toBe((string) $pedidoDeTienda->id);
});

test('una comision del storefront no inventa pedido central (#1)', function () {
    // El checkout de tienda no pasa por ningun pedido central, asi que su comision debe
    // quedarse con `central_order_id` a null en vez de apuntar a cualquier cosa.
    $comision = app(CalculateAndRecordOrderCommissionUseCase::class)->execute(
        tenantId: $this->tenantA->id,
        orderId: (string) Str::uuid(),
        orderNumber: 'ORD-SOLO-TIENDA',
        orderTotal: 100.0,
    );

    expect($comision->central_order_id)->toBeNull()
        ->and($comision->order)->toBeNull();
});

/**
 * Fase 4 del plan de wallet y retiros: el pedido de cada tienda **hereda** la tasa del pedido
 * central en vez de capturar la suya.
 *
 * El comprador hace **un** pago a **una** tasa. El despacho corre en un job que puede
 * ejecutarse horas después —o reintentarse al día siguiente—, y si cada tienda capturara la
 * tasa vigente en ese momento, la suma de los bolívares de las tiendas no cuadraría con lo que
 * el cliente pagó. La wallet de cada comerciante se calcula con esa tasa, así que el descuadre
 * sería dinero.
 *
 * Este test no existía cuando se implementó: el único fichero que ejercita el despacho es
 * éste, y estaba en rojo por el entorno de la suite (ver
 * `planes/anotaciones/ENTORNO_DE_TESTS.md`). Se añade en cuanto se pudo ejecutar.
 */
test('el pedido de cada tienda hereda la tasa del pedido central, no captura la suya', function () {
    app(ExchangeRateRepositoryInterface::class)->save(
        ExchangeRate::create(
            new LaravelUuidGenerator,
            CurrencyCode::usd(),
            CurrencyCode::ves(),
            RateAmount::make(300.0000),
            RateSource::bcv(),
            RateDate::make('2026-01-15')
        )
    );

    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Tasa Heredada', 'email' => 'tasa@example.com'],
        'shipping_address' => ['address' => 'Av. Principal', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'payment_details' => ['reference_number' => 'PM-TASA-'.random_int(100000, 999999)],
        'items' => [
            [
                'tenant_id' => $this->tenantA->id,
                'product_id' => $this->productA->id,
                'product_name' => $this->productA->name,
                'sku' => $this->productA->sku,
                'price' => 50.00,
                'quantity' => 1,
            ],
            [
                'tenant_id' => $this->tenantB->id,
                'product_id' => $this->productB->id,
                'product_name' => $this->productB->name,
                'sku' => $this->productB->sku,
                'price' => 100.00,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertStatus(201);

    $masterOrder = CentralOrder::with('items')->find($response->json('data.order_id'));
    expect((float) $masterOrder->exchange_rate)->toBe(300.0000);

    // Y mientras tanto la tasa cambia: el despacho ya ocurrió, pero aunque no hubiera
    // ocurrido, lo que se hereda es la del pedido central y no la vigente.
    app(ExchangeRateRepositoryInterface::class)->save(
        ExchangeRate::create(
            new LaravelUuidGenerator,
            CurrencyCode::usd(),
            CurrencyCode::ves(),
            RateAmount::make(900.0000),
            RateSource::bcv(),
            RateDate::make('2026-01-16')
        )
    );

    foreach ([$this->tenantA, $this->tenantB] as $tenant) {
        $item = $masterOrder->items->firstWhere('tenant_id', $tenant->id);
        expect($item->tenant_order_id)->not->toBeNull();

        tenancy()->initialize($tenant);
        $localOrder = DB::table('orders')->where('id', $item->tenant_order_id)->first();
        expect((float) $localOrder->exchange_rate)->toBe(300.0000);
        tenancy()->end();
    }
});

/*
|--------------------------------------------------------------------------
| El `handle()` del job, que hasta ahora no probaba nadie
|--------------------------------------------------------------------------
|
| Los tres tests de N17 de arriba ejercitan el CASO DE USO. El job que lo envuelve tiene
| lógica propia y ninguna estaba cubierta: decidir si el despacho quedó incompleto y
| lanzar para que la cola reintente. Si ese `throw` desapareciera, un despacho parcial
| parecería exitoso y no se reintentaría nunca — un pedido cobrado que no llega a su
| tienda, que es justo lo que el propio job dice que no puede pasar.
|
| No se veía porque hasta hoy la cola de los tests apuntaba a un redis real y los jobs no
| llegaban a ejecutarse (ver `planes/anotaciones/ENTORNO_DE_TESTS.md`).
*/

/** Deja el pedido central despachado a medias: la tienda A en `failed` y sin pedido local. */
function dejarDespachoFallido(object $test, CentralOrder $order, int $intentos = 1): void
{
    tenancy()->initialize($test->tenantA);
    DB::table('payments')->delete();
    DB::table('order_items')->delete();
    DB::table('orders')->delete();
    tenancy()->end();

    CentralOrderDispatch::where('central_order_id', $order->id)
        ->where('tenant_id', $test->tenantA->id)
        ->update([
            'status' => 'failed',
            'tenant_order_id' => null,
            'attempts' => $intentos,
            'error_message' => 'BD de la tienda caída',
        ]);
}

/** Un pedido central de una sola tienda, ya despachado. */
function pedidoCentralDeUnaTienda(object $test): CentralOrder
{
    $response = $test->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Job', 'email' => 'job-'.Str::random(5).'@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'items' => [
            ['tenant_id' => $test->tenantA->id, 'product_id' => $test->productA->id, 'quantity' => 1],
        ],
    ]);
    $response->assertStatus(201);

    return CentralOrder::with(['items', 'customer'])->find($response->json('data.order_id'));
}

test('el job lanza si una tienda queda pendiente, para que la cola reintente (N17)', function () {
    $order = pedidoCentralDeUnaTienda($this);

    dejarDespachoFallido($this, $order, intentos: 1);

    // Y la tienda vuelve a fallar en esta pasada: se queda sin existencias entre el pedido
    // y el reintento, asi que `StockReserver` lanza y su fila sigue en `failed`. No hace
    // falta ningun doble: falla de verdad, por un motivo que pasa de verdad.
    tenancy()->initialize($this->tenantA);
    DB::table('products')->where('id', $this->productA->id)->update(['quantity' => 0]);
    tenancy()->end();

    // El caso de uso NO propaga ese fallo --a proposito: el fallo de una tienda no puede
    // abortar las demas--, asi que es el job quien tiene que detectarlo mirando la tabla.
    // Sin este `throw`, un despacho parcial pareceria exitoso y no se reintentaria nunca:
    // un pedido cobrado que no llega a su tienda.
    $job = new DispatchCentralOrderJob($order->id);

    expect(fn () => $job->handle(app(DispatchCentralOrderToTenantsUseCase::class)))
        ->toThrow(RuntimeException::class);

    expect(CentralOrderDispatch::where('central_order_id', $order->id)->first()->status)
        ->toBe('failed');
});

test('el job no lanza si la tienda ya agoto sus intentos (N17)', function () {
    $order = pedidoCentralDeUnaTienda($this);

    // Con el tope gastado, el caso de uso ni la toca y el job tampoco lanza: machacar una
    // tienda rota solo llena la cola de trabajo inutil. Queda para intervencion manual.
    dejarDespachoFallido($this, $order, intentos: DispatchCentralOrderToTenantsUseCase::MAX_DISPATCH_ATTEMPTS);

    $job = new DispatchCentralOrderJob($order->id);
    $job->handle(app(DispatchCentralOrderToTenantsUseCase::class));

    expect(CentralOrderDispatch::where('central_order_id', $order->id)->first()->status)
        ->toBe('failed');
});

test('el job sale sin gastar reintentos si el pedido central ya no existe (N17)', function () {
    $job = new DispatchCentralOrderJob((string) Str::uuid());

    // Sin excepcion: reintentar cinco veces algo que no existe no lo va a resucitar, y
    // cada reintento es trabajo inutil en la cola.
    $job->handle(app(DispatchCentralOrderToTenantsUseCase::class));

    expect(CentralOrderDispatch::count())->toBe(0);
});
