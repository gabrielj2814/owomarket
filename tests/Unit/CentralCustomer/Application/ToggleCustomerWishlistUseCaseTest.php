<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\ToggleCustomerWishlistProductUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerWishlist;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

uses(Tests\TestCase::class);

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('central_customers')) {
        (require base_path('database/migrations/2026_08_19_000001_create_central_customers_tables.php'))->up();
    }
    if (! Schema::hasTable('central_customer_wishlists')) {
        (require base_path('database/migrations/2026_08_19_000011_create_central_customer_wishlists_table.php'))->up();
    }
});

test('ToggleCustomerWishlistProductUseCase adds and then removes item from wishlist', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Wishlist User',
        'email' => 'wish_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'secret',
    ]);

    $useCase = new ToggleCustomerWishlistProductUseCase;

    // 1. Add to wishlist
    $res1 = $useCase->execute($customer->id, [
        'product_id' => 'p-fav-1',
        'tenant_id' => 'store-tech',
        'product_name' => 'Mouse Gamer Inalámbrico',
        'product_slug' => 'mouse-gamer-inalambrico',
        'product_price' => 25.00,
        'product_image' => 'https://owomarket.local/images/mouse.jpg',
    ]);

    expect($res1['in_wishlist'])->toBeTrue();
    expect(CentralCustomerWishlist::where('customer_id', $customer->id)->count())->toBe(1);

    // 2. Remove from wishlist
    $res2 = $useCase->execute($customer->id, [
        'product_id' => 'p-fav-1',
        'tenant_id' => 'store-tech',
        'product_name' => 'Mouse Gamer Inalámbrico',
        'product_price' => 25.00,
    ]);

    expect($res2['in_wishlist'])->toBeFalse();
    expect(CentralCustomerWishlist::where('customer_id', $customer->id)->count())->toBe(0);
});
