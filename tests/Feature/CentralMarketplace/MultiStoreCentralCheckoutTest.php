<?php

declare(strict_types=1);

use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Monetization\Application\UseCases\ListSubscriptionPlansUseCase;
use Src\Monetization\Application\UseCases\SubscribeTenantToPlanUseCase;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Product\Infrastructure\Eloquent\Models\Product;
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
    if (! Schema::hasTable('orders')) {
        (require base_path('database/migrations/tenant/2025_10_28_144320_create_orders.php'))->up();
    }
    if (! Schema::hasTable('order_items')) {
        (require base_path('database/migrations/tenant/2025_10_28_144403_create_order_items.php'))->up();
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

    $refNumber = 'PM-MASTER-' . random_int(100000, 999999);

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
    expect($masterOrder->total)->toBe(210.00); // 200 + 10 shipping
    expect($masterOrder->items->count())->toBe(2);

    // 3. Verify Local Orders in Tenant A and Tenant B
    $itemA = $masterOrder->items->firstWhere('tenant_id', $this->tenantA->id);
    $itemB = $masterOrder->items->firstWhere('tenant_id', $this->tenantB->id);

    expect($itemA->tenant_order_id)->not->toBeNull();
    expect($itemB->tenant_order_id)->not->toBeNull();

    // Verify Tenant A local order in DB
    // Hallazgo D1: el envío de $10 se reparte entre las dos tiendas (mitad y
    // mitad, porque aportan $100 cada una), en vez de perderse. Antes cada
    // pedido de tienda quedaba en $100 y la suma no cuadraba con los $210 que
    // pagó el cliente.
    tenancy()->initialize($this->tenantA);
    $localOrderA = DB::table('orders')->where('id', $itemA->tenant_order_id)->first();
    expect($localOrderA)->not->toBeNull();
    expect((float) $localOrderA->total)->toBe(105.00);
    $paymentA = DB::table('payments')->where('order_id', $itemA->tenant_order_id)->first();
    expect($paymentA)->not->toBeNull();
    expect((float) $paymentA->amount)->toBe(105.00);
    expect($paymentA->payment_gateway)->toBe('pago_movil');
    tenancy()->end();

    // Verify Tenant B local order in DB
    tenancy()->initialize($this->tenantB);
    $localOrderB = DB::table('orders')->where('id', $itemB->tenant_order_id)->first();
    expect($localOrderB)->not->toBeNull();
    expect((float) $localOrderB->total)->toBe(105.00);
    $paymentB = DB::table('payments')->where('order_id', $itemB->tenant_order_id)->first();
    expect($paymentB)->not->toBeNull();
    expect((float) $paymentB->amount)->toBe(105.00);
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
    expect((float) $commA->order_total)->toBe(100.00); // base, no los $105 cobrados

    $commB = $commissions->firstWhere('tenant_id', $this->tenantB->id);
    expect($commB)->not->toBeNull();
    expect($commB->commission_rate)->toBe(8.00); // Default global plan
    expect($commB->commission_amount)->toBe(8.00); // 100 * 8%

    // 5. Verify Unified Confirmation API
    $confResponse = $this->getJson("/api/central/marketplace/order/{$centralOrderId}/confirmation");
    $confResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.stores_count', 2)
        ->assertJsonPath('data.total', 210);

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

test('El envío y el descuento se prorratean entre las tiendas (hallazgo D1)', function () {
    // Escenario numérico textual de la auditoría, adaptado a los productos del
    // test: A aporta $60 (no divisible con el balón de $50, así que se usa
    // 1 balón = $50) y B aporta $100. Envío $10, cupón −$30.
    //
    // Subtotal = 150, total central = 150 + 10 - 30 = $130.
    // Pesos: A = 50/150 = 1/3, B = 100/150 = 2/3.
    // Envío:     A = $3,33   B = $6,67   (suma exacta $10)
    // Descuento: A = $10,00  B = $20,00  (suma exacta $30)
    // Total A = 50 + 3,33 - 10 = $43,33
    // Total B = 100 + 6,67 - 20 = $86,67
    // Suma = $130 ✓
    $response = $this->postJson('/api/central/marketplace/checkout/create-order', [
        'customer' => ['name' => 'Cliente Prorrateo', 'email' => 'prorrateo@example.com'],
        'shipping_address' => ['address' => 'Calle 1', 'city' => 'Caracas'],
        'payment_method' => 'pago_movil',
        'shipping_amount' => 10.00,
        'discount_amount' => 30.00,
        'items' => [
            ['tenant_id' => $this->tenantA->id, 'product_id' => $this->productA->id, 'quantity' => 1],
            ['tenant_id' => $this->tenantB->id, 'product_id' => $this->productB->id, 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(201);

    $order = CentralOrder::with('items')->find($response->json('data.order_id'));
    expect($order->subtotal)->toBe(150.00);
    expect($order->total)->toBe(130.00);

    $itemA = $order->items->firstWhere('tenant_id', $this->tenantA->id);
    $itemB = $order->items->firstWhere('tenant_id', $this->tenantB->id);

    tenancy()->initialize($this->tenantA);
    $localA = DB::table('orders')->where('id', $itemA->tenant_order_id)->first();
    expect((float) $localA->shipping_amount)->toBe(3.33);
    expect((float) $localA->discount_amount)->toBe(10.00);
    expect((float) $localA->total)->toBe(43.33);
    tenancy()->end();

    tenancy()->initialize($this->tenantB);
    $localB = DB::table('orders')->where('id', $itemB->tenant_order_id)->first();
    expect((float) $localB->shipping_amount)->toBe(6.67);
    expect((float) $localB->discount_amount)->toBe(20.00);
    expect((float) $localB->total)->toBe(86.67);
    tenancy()->end();

    // Lo esencial: la suma de los pedidos de tienda es EXACTAMENTE lo que pagó
    // el cliente. Ni un céntimo perdido ni inventado por el redondeo.
    expect(round((float) $localA->total + (float) $localB->total, 2))->toBe(130.00);

    // Y la comisión se cobra sobre la mercancía neta de descuento (sin envío):
    // A → 50 - 10 = $40 ; B → 100 - 20 = $80.
    $commA = PlatformCommission::where('order_id', $itemA->tenant_order_id)->first();
    $commB = PlatformCommission::where('order_id', $itemB->tenant_order_id)->first();
    expect((float) $commA->order_total)->toBe(40.00);
    expect((float) $commB->order_total)->toBe(80.00);
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
