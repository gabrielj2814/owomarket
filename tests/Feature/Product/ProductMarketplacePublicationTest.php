<?php

declare(strict_types=1);

use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Product\Infrastructure\Eloquent\Models\Product as EloquentProduct;
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
    if (! Schema::hasTable('categories')) {
        (require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php'))->up();
    }
    if (! Schema::hasTable('brands')) {
        (require base_path('database/migrations/tenant/2025_10_28_143000_create_brands.php'))->up();
    }
    if (! Schema::hasTable('products')) {
        (require base_path('database/migrations/tenant/2025_10_28_143038_create_products.php'))->up();
    }
    if (! Schema::hasTable('product_images')) {
        (require base_path('database/migrations/tenant/2025_10_28_143251_create_product_images.php'))->up();
    }
    if (! Schema::hasTable('product_variants')) {
        (require base_path('database/migrations/tenant/2025_10_28_143954_create_product_variants.php'))->up();
    }
    if (! Schema::hasColumn('products', 'category_id')) {
        (require base_path('database/migrations/tenant/2026_08_18_000004_add_category_and_brand_to_products_table.php'))->up();
    }
    if (! Schema::hasColumn('products', 'is_published_central')) {
        (require base_path('database/migrations/tenant/2026_08_19_000006_add_marketplace_publication_to_products_table.php'))->up();
    }

    // Ensure central tables exist
    if (! Schema::hasTable('central_products')) {
        (require base_path('database/migrations/2026_08_19_000007_create_central_products_table.php'))->up();
    }

    $tenantId = 't_pub_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Publicacion Test',
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
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

test('Product by default is not published in central marketplace and can be published, unpublished and synced on demand', function () {
    $category = Category::create([
        'name' => 'Calzado Deportivo',
        'slug' => 'calzado-deportivo-'.Str::random(4),
        'is_active' => true,
    ]);

    // 1. Create product (default is_published_central = false)
    $product = EloquentProduct::create([
        'id' => (string) Str::uuid(),
        'name' => 'Botas Trekking Montaña Pro',
        'slug' => 'botas-trekking-pro-'.Str::random(4),
        'sku' => 'BOT-TRK-01',
        'price' => 120.00,
        'quantity' => 15,
        'category_id' => $category->id,
        'is_visible' => true, // Visible in tenant storefront
        'is_published_central' => false, // Not published in central marketplace
    ]);

    expect($product->is_published_central)->toBeFalse();

    // Verify it is NOT in central_products
    $centralProd = CentralProduct::where('tenant_id', $this->tenant->id)
        ->where('tenant_product_id', $product->id)
        ->first();
    expect($centralProd)->toBeNull();

    // 2. Publish to Central Marketplace via API
    $publishResponse = $this->postJson("http://{$this->domain}/api-tenant/product/{$product->id}/toggle-marketplace", [
        'is_published_central' => true,
    ]);

    $publishResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.is_published_central', true);

    // Verify now exists in central_products
    $centralProd = CentralProduct::where('tenant_id', $this->tenant->id)
        ->where('tenant_product_id', $product->id)
        ->first();

    expect($centralProd)->not->toBeNull();
    expect($centralProd->name)->toBe('Botas Trekking Montaña Pro');
    expect((float) $centralProd->price)->toBe(120.00);
    expect($centralProd->quantity)->toBe(15);
    expect($centralProd->is_visible)->toBeTrue();
    expect($centralProd->category_name)->toBe('Calzado Deportivo');

    // 3. Update stock in Tenant and verify Central Product stock syncs
    $stockResponse = $this->patchJson("http://{$this->domain}/api-tenant/product/{$product->id}/stock", [
        'quantity' => 42,
    ]);
    $stockResponse->assertStatus(200);

    $centralProd->refresh();
    expect($centralProd->quantity)->toBe(42);

    // 4. Unpublish / Dar de baja from Central Marketplace
    $unpublishResponse = $this->postJson("http://{$this->domain}/api-tenant/product/{$product->id}/toggle-marketplace", [
        'is_published_central' => false,
    ]);

    $unpublishResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.is_published_central', false);

    // Central product must be hidden (is_visible = false)
    $centralProd->refresh();
    expect($centralProd->is_visible)->toBeFalse();

    // But product still exists in tenant store and can be queried for invoicing / POS
    $localProd = EloquentProduct::find($product->id);
    expect($localProd)->not->toBeNull();
    expect($localProd->is_visible)->toBeTrue();
    expect($localProd->is_published_central)->toBeFalse();
    expect($localProd->quantity)->toBe(42);
});
