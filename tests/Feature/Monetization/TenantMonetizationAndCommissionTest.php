<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Monetization\Application\UseCases\CalculateAndRecordOrderCommissionUseCase;
use Src\Monetization\Application\UseCases\ListSubscriptionPlansUseCase;
use Src\Monetization\Application\UseCases\SubscribeTenantToPlanUseCase;
use Src\Monetization\Application\UseCases\UpdateTenantCustomCommissionUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
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

    // Ensure central monetization tables exist
    if (! Schema::hasTable('subscription_plans')) {
        (require base_path('database/migrations/2026_08_19_000003_create_monetization_tables.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Test Monetización',
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

    // Fase 0.3-E: /api-tenant/* exige sesión de usuario de la tienda.
    $this->tenantUser = \Src\Tenant\Infrastructure\Eloquent\Models\User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Store Staff',
        'email' => 'staff_'.bin2hex(random_bytes(5)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);
    $this->actingAs($this->tenantUser);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('ListSubscriptionPlansUseCase boots default Free, Pro and Enterprise plans', function () {
    $useCase = app(ListSubscriptionPlansUseCase::class);
    $plans = $useCase->execute();

    expect($plans->count())->toBeGreaterThanOrEqual(3);
    $slugs = $plans->pluck('slug')->toArray();
    expect($slugs)->toContain('free', 'pro', 'enterprise');

    $pro = $plans->firstWhere('slug', 'pro');
    expect($pro->commission_rate)->toBe(3.50);
});

test('CalculateAndRecordOrderCommissionUseCase resolves 3-tier hierarchy accurately', function () {
    $useCase = app(CalculateAndRecordOrderCommissionUseCase::class);
    $subscribeUseCase = app(SubscribeTenantToPlanUseCase::class);
    $customRateUseCase = app(UpdateTenantCustomCommissionUseCase::class);

    // Bootstrap plans
    app(ListSubscriptionPlansUseCase::class)->execute();

    // 1. Tier 3: Global default (8.00%)
    $orderId1 = (string) Str::uuid();
    $comm1 = $useCase->execute(
        tenantId: $this->tenant->id,
        orderId: $orderId1,
        orderNumber: 'ORD-001',
        orderTotal: 200.00,
        paymentGateway: 'pago_movil'
    );
    expect($comm1->commission_rate)->toBe(8.00);
    expect($comm1->commission_amount)->toBe(16.00); // 200 * 8%
    // Hallazgo N15: la comisión nace en `awaiting_payment` —devengada pero NO cobrable—
    // y sólo pasa a `pending` cuando el pago se confirma.
    expect($comm1->status)->toBe('awaiting_payment');

    // 2. Tier 2: Subscription Plan Pro (3.50%)
    $subscribeUseCase->execute($this->tenant->id, 'pro', 'monthly');

    $orderId2 = (string) Str::uuid();
    $comm2 = $useCase->execute(
        tenantId: $this->tenant->id,
        orderId: $orderId2,
        orderNumber: 'ORD-002',
        orderTotal: 200.00,
        paymentGateway: 'binance_pay'
    );
    expect($comm2->commission_rate)->toBe(3.50);
    expect($comm2->commission_amount)->toBe(7.00); // 200 * 3.5%

    // 3. Tier 1: Custom Tenant Override (e.g. 1.75%)
    $customRateUseCase->execute($this->tenant->id, 1.75);

    $orderId3 = (string) Str::uuid();
    $comm3 = $useCase->execute(
        tenantId: $this->tenant->id,
        orderId: $orderId3,
        orderNumber: 'ORD-003',
        orderTotal: 200.00,
        paymentGateway: 'binance_pay'
    );
    expect($comm3->commission_rate)->toBe(1.75);
    expect($comm3->commission_amount)->toBe(3.50); // 200 * 1.75%
});

test('Tenant can subscribe to plan and inspect monetization summary via API', function () {
    app(ListSubscriptionPlansUseCase::class)->execute();

    // 1. Get plans
    $plansResponse = $this->getJson("http://{$this->domain}/monetization/plans");
    $plansResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');

    // 2. Subscribe to Enterprise plan
    $subResponse = $this->postJson("http://{$this->domain}/monetization/subscribe", [
        'plan' => 'enterprise',
        'billing_cycle' => 'yearly',
    ]);
    $subResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.plan.slug', 'enterprise');

    // 3. Get summary
    $summaryResponse = $this->getJson("http://{$this->domain}/monetization/summary");
    $summaryResponse->assertStatus(200)
        ->assertJsonPath('data.plan.slug', 'enterprise');
    expect((float) $summaryResponse->json('data.effective_commission_rate'))->toBe(1.0);
});

test('Storefront checkout automatically records platform commission in central database', function () {
    app(ListSubscriptionPlansUseCase::class)->execute();

    // Subscribe to Pro plan (3.5% fee)
    app(SubscribeTenantToPlanUseCase::class)->execute($this->tenant->id, 'pro');

    $category = Category::create([
        'name' => 'Monetized Category',
        'slug' => 'monetized-category-'.Str::random(5),
        'is_active' => true,
    ]);

    $product = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Monetized Product',
        'slug' => 'monetized-product-'.Str::random(5),
        'sku' => 'MON-001',
        'price' => 100.00,
        'quantity' => 20,
        'category_id' => $category->id,
        'is_visible' => true,
    ]);

    $payload = [
        'customer' => [
            'name' => 'Carlos Monetizado',
            'email' => 'carlos@example.com',
            'phone' => '+584120001122',
        ],
        'shipping_address' => [
            'address' => 'Av. Principal',
            'city' => 'Caracas',
        ],
        'shipping_method' => 'standard',
        'shipping_amount' => 10.00,
        'payment_method' => 'pago_movil',
        'payment_details' => [
            'bank_origin' => 'Mercantil',
            'reference_number' => 'REF-998877',
        ],
        'items' => [
            [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'price' => 100.00,
                'quantity' => 1,
            ],
        ],
    ];

    $response = $this->postJson("http://{$this->domain}/checkout/create-order", $payload);
    $response->assertStatus(201);

    $orderId = $response->json('data.order_id');

    // Verify PlatformCommission record in central DB
    $commission = PlatformCommission::where('order_id', $orderId)->first();
    expect($commission)->not->toBeNull();
    expect($commission->tenant_id)->toBe($this->tenant->id);
    expect($commission->order_total)->toBe(110.00); // 100 + 10 shipping
    expect($commission->commission_rate)->toBe(3.50);
    expect($commission->commission_amount)->toBe(3.85); // 110 * 3.5% = 3.85
    // Hallazgo N15: la comisión nace en `awaiting_payment` —devengada pero NO cobrable—
    // y sólo pasa a `pending` cuando el pago se confirma.
    expect($commission->status)->toBe('awaiting_payment');
    expect($commission->payment_gateway)->toBe('pago_movil');
});

test('Cancelar un pedido anula la comisión de la plataforma (hallazgo D2)', function () {
    // No hace falta suscribir a ningún plan: lo que se prueba es que la
    // comisión se anula, sea cual sea su tasa.
    $category = Category::create([
        'name' => 'Cancelables',
        'slug' => 'cancelables-'.Str::random(5),
        'is_active' => true,
    ]);

    $product = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Producto Cancelable',
        'slug' => 'producto-cancelable-'.Str::random(5),
        'sku' => 'CANCEL-001',
        'price' => 1000.00,
        'quantity' => 5,
        'category_id' => $category->id,
        'is_visible' => true,
    ]);

    // Escenario textual de la auditoría: el cliente pide $1.000 con Pago Móvil
    // —cuyo payment_status nace siempre 'pending'— y nunca paga.
    $response = $this->postJson("http://{$this->domain}/checkout/create-order", [
        'customer' => ['name' => 'Cliente Moroso', 'email' => 'moroso@example.com'],
        'shipping_address' => ['address' => 'Av. Principal', 'city' => 'Caracas'],
        'shipping_method' => 'standard',
        'payment_method' => 'pago_movil',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);
    $response->assertStatus(201);
    $orderId = $response->json('data.order_id');

    $commission = PlatformCommission::where('order_id', $orderId)->first();
    expect($commission)->not->toBeNull();
    // Hallazgo N15: la comisión nace en `awaiting_payment` —devengada pero NO cobrable—
    // y sólo pasa a `pending` cuando el pago se confirma.
    expect($commission->status)->toBe('awaiting_payment');

    // La tienda cancela porque el pago nunca llegó.
    $cancelResponse = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/cancel", [
        'reason' => 'El cliente nunca reportó el pago',
    ]);
    $cancelResponse->assertStatus(200);

    // Antes la comisión seguía en 'pending' y entraba en la siguiente
    // liquidación: se le cobraba a la tienda por una venta que no existió.
    $commission->refresh();
    expect($commission->status)->toBe('waived');
    expect($commission->metadata['reversal']['reason'])->toBe('order_cancelled');

    // Y ya no la recoge el generador de liquidaciones.
    $pendientes = PlatformCommission::where('tenant_id', $this->tenant->id)
        ->whereIn('status', ['pending', 'awaiting_payment'])
        ->whereNull('settlement_id')
        ->count();
    expect($pendientes)->toBe(0);
});

test('Reembolsar un pedido marca la comisión como devuelta (hallazgo D2)', function () {
    $category = Category::create([
        'name' => 'Reembolsables',
        'slug' => 'reembolsables-'.Str::random(5),
        'is_active' => true,
    ]);

    $product = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Producto Reembolsable',
        'slug' => 'producto-reembolsable-'.Str::random(5),
        'sku' => 'REF-001',
        'price' => 200.00,
        'quantity' => 5,
        'category_id' => $category->id,
        'is_visible' => true,
    ]);

    $response = $this->postJson("http://{$this->domain}/checkout/create-order", [
        'customer' => ['name' => 'Cliente Devolución', 'email' => 'devolucion@example.com'],
        'shipping_address' => ['address' => 'Av. Principal', 'city' => 'Caracas'],
        'shipping_method' => 'standard',
        'payment_method' => 'pago_movil',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);
    $response->assertStatus(201);
    $orderId = $response->json('data.order_id');

    // El pedido avanza hasta un estado desde el que se puede reembolsar.
    foreach (['confirmed', 'processing', 'shipped', 'delivered'] as $estado) {
        $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/status", [
            'status' => $estado,
        ]);
    }

    $refundResponse = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/status", [
        'status' => 'refunded',
    ]);
    $refundResponse->assertStatus(200);

    $commission = PlatformCommission::where('order_id', $orderId)->first();
    expect($commission->status)->toBe('refunded');
    expect($commission->metadata['reversal']['reason'])->toBe('order_refunded');
});

test('Revertir una comisión ya liquidada la marca para ajuste manual (hallazgo D2)', function () {
    $orderId = (string) Str::uuid();

    $commission = PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $orderId,
        'order_number' => 'ORD-YA-LIQUIDADA',
        'order_total' => 500.00,
        'commission_rate' => 8.00,
        'commission_amount' => 40.00,
        'currency' => 'USD',
        // 'collected' basta para disparar la marca de ajuste manual, sin
        // necesidad de una liquidación real (settlement_id tiene clave foránea).
        'status' => 'collected',
    ]);

    app(\Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase::class)
        ->execute($orderId, 'order_refunded');

    $commission->refresh();

    // Se marca para que no vuelva a liquidarse...
    expect($commission->status)->toBe('refunded');
    // ...pero queda señalada: puede que a la tienda ya se le haya descontado,
    // así que hace falta una nota de crédito en la próxima liquidación.
    expect($commission->metadata['reversal']['requires_manual_adjustment'])->toBeTrue();
    expect($commission->metadata['reversal']['previous_status'])->toBe('collected');
});
