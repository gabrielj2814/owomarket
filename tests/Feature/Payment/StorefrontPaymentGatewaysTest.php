<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Brand\Infrastructure\Eloquent\Models\Brand;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Customer\Infrastructure\Eloquent\Models\Customer;
use Src\Payment\Application\Contracts\PaymentGatewayFactoryInterface;
use Src\Payment\Infrastructure\Adapters\BinancePayPaymentGateway;
use Src\Payment\Infrastructure\Adapters\PagoMovilPaymentGateway;
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

    // Ensure tenant tables exist in test db
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

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Test Pagos',
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

    // Create Category & Product
    $category = Category::create([
        'name' => 'Calzado Deportivo',
        'slug' => 'calzado-deportivo-'.Str::random(5),
        'is_active' => true,
    ]);

    $this->product = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Zapatillas Nike Pegasus',
        'slug' => 'zapatillas-nike-pegasus-'.Str::random(5),
        'sku' => 'NIKE-PEG-01',
        'price' => 120.00,
        'quantity' => 15,
        'category_id' => $category->id,
        'is_visible' => true,
    ]);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('PaymentGatewayFactory resolves PagoMovil and BinancePay adapters', function () {
    /** @var PaymentGatewayFactoryInterface $factory */
    $factory = app(PaymentGatewayFactoryInterface::class);

    $pagoMovil = $factory->make('pago_movil');
    expect($pagoMovil)->toBeInstanceOf(PagoMovilPaymentGateway::class);
    expect($pagoMovil->getIdentifier())->toBe('pago_movil');

    $binancePay = $factory->make('binance_pay');
    expect($binancePay)->toBeInstanceOf(BinancePayPaymentGateway::class);
    expect($binancePay->getIdentifier())->toBe('binance_pay');
});

test('Storefront checkout creates order and records payment with Pago Movil details', function () {
    $refNumber = 'PM-REF-' . random_int(100000, 999999);

    $payload = [
        'customer' => [
            'name' => 'Roberto Sánchez',
            'email' => 'roberto.sanchez@example.com',
            'phone' => '+584121112233',
            'document_id' => 'V-20123456',
        ],
        'shipping_address' => [
            'address' => 'Av. Francisco de Miranda, Edif. Torre Polar, Piso 4',
            'city' => 'Caracas',
            'state' => 'Distrito Capital',
            'zip' => '1060',
            'notes' => 'Entregar en horario de oficina',
        ],
        'shipping_method' => 'standard',
        'shipping_amount' => 5.00,
        'payment_method' => 'pago_movil',
        'payment_details' => [
            'bank_origin' => 'Banesco Banco Universal',
            'phone_origin' => '04121112233',
            'reference_number' => $refNumber,
        ],
        'items' => [
            [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => 120.00,
                'quantity' => 1,
            ],
        ],
    ];

    $response = $this->postJson("http://{$this->domain}/checkout/create-order", $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'code' => 201,
        ]);

    $orderId = $response->json('data.order_id');
    expect($orderId)->not->toBeNull();

    // Verify order in database
    $order = DB::table('orders')->where('id', $orderId)->first();
    expect($order)->not->toBeNull();
    expect($order->payment_method)->toBe('pago_movil');
    expect((float) $order->total)->toBe(125.00);

    // Verify payment in payments table
    $payment = DB::table('payments')->where('order_id', $orderId)->first();
    expect($payment)->not->toBeNull();
    expect($payment->payment_gateway)->toBe('pago_movil');
    expect($payment->status)->toBe('pending');
    expect($payment->transaction_id)->toBe($refNumber);
});

test('Storefront checkout creates order and records payment with Binance Pay USDT details', function () {
    $txHash = 'BN-TX-' . Str::random(16);

    $payload = [
        'customer' => [
            'name' => 'Maria Delgado',
            'email' => 'maria.delgado@example.com',
            'phone' => '+584249998877',
        ],
        'shipping_address' => [
            'address' => 'Calle 5 con Av 3, Los Palos Grandes',
            'city' => 'Caracas',
            'state' => 'Miranda',
        ],
        'shipping_method' => 'express',
        'shipping_amount' => 9.00,
        'payment_method' => 'binance_pay',
        'payment_details' => [
            'binance_id' => '987654321',
            'transaction_hash' => $txHash,
            'crypto_currency' => 'USDT',
        ],
        'items' => [
            [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => 120.00,
                'quantity' => 2,
            ],
        ],
    ];

    $response = $this->postJson("http://{$this->domain}/checkout/create-order", $payload);

    $response->assertStatus(201);

    $orderId = $response->json('data.order_id');
    $order = DB::table('orders')->where('id', $orderId)->first();
    expect($order)->not->toBeNull();
    expect($order->payment_method)->toBe('binance_pay');
    expect((float) $order->total)->toBe(249.00);

    $payment = DB::table('payments')->where('order_id', $orderId)->first();
    expect($payment)->not->toBeNull();
    expect($payment->payment_gateway)->toBe('binance_pay');
    expect($payment->transaction_id)->toBe($txHash);
});
