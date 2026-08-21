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
    tenancy()->initialize($this->tenantA);
    $localOrderA = DB::table('orders')->where('id', $itemA->tenant_order_id)->first();
    expect($localOrderA)->not->toBeNull();
    expect((float) $localOrderA->total)->toBe(100.00);
    $paymentA = DB::table('payments')->where('order_id', $itemA->tenant_order_id)->first();
    expect($paymentA)->not->toBeNull();
    expect($paymentA->payment_gateway)->toBe('pago_movil');
    tenancy()->end();

    // Verify Tenant B local order in DB
    tenancy()->initialize($this->tenantB);
    $localOrderB = DB::table('orders')->where('id', $itemB->tenant_order_id)->first();
    expect($localOrderB)->not->toBeNull();
    expect((float) $localOrderB->total)->toBe(100.00);
    $paymentB = DB::table('payments')->where('order_id', $itemB->tenant_order_id)->first();
    expect($paymentB)->not->toBeNull();
    tenancy()->end();

    // 4. Verify Platform Commissions in Central DB
    $commissions = PlatformCommission::where('order_id', $itemA->tenant_order_id)
        ->orWhere('order_id', $itemB->tenant_order_id)
        ->get();

    expect($commissions->count())->toBe(2);

    $commA = $commissions->firstWhere('tenant_id', $this->tenantA->id);
    expect($commA)->not->toBeNull();
    expect($commA->commission_rate)->toBe(3.50); // Pro plan
    expect($commA->commission_amount)->toBe(3.50); // 100 * 3.5%

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
