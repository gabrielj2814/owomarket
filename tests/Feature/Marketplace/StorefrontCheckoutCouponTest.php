<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Coupon\Infrastructure\Eloquent\Models\Coupon;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Hallazgos B3 y C6.
 *
 * El checkout **no usaba** `ValidateCouponUseCase` ni `Coupon::validateUsability()`: sólo
 * comprobaba `is_active`, así que ignoraba `valid_from`/`valid_to`, `usage_limit` y
 * `min_order_amount`. Un cupón `NAVIDAD2025` con `valid_to = 2025-12-31` y su límite de
 * usos agotado seguía descontando en agosto de 2026, de forma ilimitada.
 *
 * Y el `used_count` se incrementaba con `increment()` sin comprobar el techo, así que N
 * peticiones paralelas pasaban todas la comprobación previa.
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
        'product_variants' => '2025_10_28_143954_create_product_variants',
        'product_images' => '2025_10_28_143251_create_product_images',
        'customers' => '2025_10_28_144201_create_customers',
        'orders' => '2025_10_28_144320_create_orders',
        'order_items' => '2025_10_28_144403_create_order_items',
        'payments' => '2025_10_28_144517_create_payments',
        'coupons' => '2025_10_28_144655_create_coupons',
    ] as $table => $migration) {
        if (! Schema::hasTable($table)) {
            (require base_path("database/migrations/tenant/{$migration}.php"))->up();
        }
    }

    $tenantId = 't_cup_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Cupones',
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
        'price' => 100.00,
        'quantity' => 50,
        'is_visible' => true,
        'track_quantity' => true,
    ]);

    $this->checkout = function (?string $couponCode = null) {
        return $this->postJson("http://{$this->domain}/checkout/create-order", array_filter([
            'customer' => ['name' => 'Ana Pérez', 'email' => 'ana@example.com'],
            'shipping_address' => ['address' => 'Av. Libertador 1', 'city' => 'Caracas'],
            'shipping_method' => 'standard',
            'payment_method' => 'bank_transfer',
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 1,
                'price' => 100.00,
            ]],
            'coupon_code' => $couponCode,
        ], fn ($v) => $v !== null));
    };
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function crearCupon(array $overrides = []): Coupon
{
    return Coupon::create(array_merge([
        'id' => (string) Str::uuid(),
        'code' => 'NAVIDAD2025',
        'type' => 'percentage',
        'value' => 10.00,
        'valid_from' => '2025-12-01',
        'valid_to' => '2025-12-31',
        'is_active' => true,
        'used_count' => 0,
    ], $overrides));
}

test('un cupón caducado ya no descuenta', function () {
    crearCupon();

    // `valid_to` fue el 31/12/2025 y hoy es 2026: antes seguía descontando igualmente.
    ($this->checkout)('NAVIDAD2025')->assertStatus(422);

    expect(Coupon::where('code', 'NAVIDAD2025')->value('used_count'))->toBe(0);
});

test('un cupón con el límite de usos agotado ya no descuenta', function () {
    crearCupon([
        'code' => 'AGOTADO',
        'valid_from' => now()->subMonth()->toDateString(),
        'valid_to' => now()->addMonth()->toDateString(),
        'usage_limit' => 100,
        'used_count' => 100,
    ]);

    ($this->checkout)('AGOTADO')->assertStatus(422);

    expect(Coupon::where('code', 'AGOTADO')->value('used_count'))->toBe(100);
});

test('un cupón por debajo del monto mínimo ya no descuenta', function () {
    crearCupon([
        'code' => 'MINIMO500',
        'valid_from' => now()->subMonth()->toDateString(),
        'valid_to' => now()->addMonth()->toDateString(),
        'min_order_amount' => 500.00,
    ]);

    // El pedido es de $100 y el mínimo son $500.
    ($this->checkout)('MINIMO500')->assertStatus(422);
});

test('un cupón válido descuenta y consume un uso', function () {
    crearCupon([
        'code' => 'VIGENTE',
        'valid_from' => now()->subMonth()->toDateString(),
        'valid_to' => now()->addMonth()->toDateString(),
        'usage_limit' => 5,
    ]);

    ($this->checkout)('VIGENTE')->assertStatus(201);

    expect(Coupon::where('code', 'VIGENTE')->value('used_count'))->toBe(1);
    expect((float) DB::table('orders')->value('discount_amount'))->toBe(10.00);
});

// C6: `increment()` es atómico por columna pero no comprueba el techo, así que la
// comprobación previa y el incremento eran dos sentencias y N peticiones paralelas
// pasaban todas.
test('el consumo del cupón no puede superar su límite de usos', function () {
    crearCupon([
        'code' => 'ULTIMO',
        'valid_from' => now()->subMonth()->toDateString(),
        'valid_to' => now()->addMonth()->toDateString(),
        'usage_limit' => 1,
        'used_count' => 1,
    ]);

    $redeemer = app(Src\Marketplace\Application\Service\CouponRedeemer::class);

    expect(fn () => $redeemer->redeem('ULTIMO'))->toThrow(Exception::class);
    expect(Coupon::where('code', 'ULTIMO')->value('used_count'))->toBe(1);
});

test('un pedido sin cupón sigue funcionando', function () {
    ($this->checkout)()->assertStatus(201);

    expect((float) DB::table('orders')->value('discount_amount'))->toBe(0.00);
});
