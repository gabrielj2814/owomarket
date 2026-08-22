<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    $this->withoutVite();

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    // Ensure central tables exist
    if (! Schema::hasTable('central_products')) {
        (require base_path('database/migrations/2026_08_19_000007_create_central_products_table.php'))->up();
    }

    $tenant1Id = 't_store1_'.bin2hex(random_bytes(2));
    $this->tenant1 = ModelsTenant::create([
        'id' => $tenant1Id,
        'name' => 'Tienda Ropa Urbana',
        'slug' => $tenant1Id,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain1 = "{$tenant1Id}.localhost";
    $this->tenant1->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain1,
    ]);

    $tenant2Id = 't_store2_'.bin2hex(random_bytes(2));
    $this->tenant2 = ModelsTenant::create([
        'id' => $tenant2Id,
        'name' => 'TechStore Venezuela',
        'slug' => $tenant2Id,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain2 = "{$tenant2Id}.localhost";
    $this->tenant2->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain2,
    ]);

    // Seed Central Products for Tenant 1
    $this->prod1 = CentralProduct::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant1->id,
        'tenant_product_id' => (string) Str::uuid(),
        'name' => 'Camiseta Oversize Streetwear',
        'slug' => 'camiseta-oversize-streetwear-'.Str::random(4),
        'description' => 'Camiseta 100% algodón peinado.',
        'sku' => 'TSH-OVR-01',
        'price' => 25.00,
        'quantity' => 20,
        'is_visible' => true,
        'is_featured' => true,
        'category_name' => 'Moda y Ropa',
        'brand_name' => 'UrbanOwO',
        'images' => [
            ['image_path' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518', 'is_default' => true],
        ],
    ]);

    // Seed Central Products for Tenant 2
    $this->prod2 = CentralProduct::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant2->id,
        'tenant_product_id' => (string) Str::uuid(),
        'name' => 'Mouse Gamer Inalámbrico RGB',
        'slug' => 'mouse-gamer-rgb-'.Str::random(4),
        'description' => 'Sensor óptico 16000 DPI con batería de 70 horas.',
        'sku' => 'MOU-GMR-02',
        'price' => 45.00,
        'quantity' => 15,
        'is_visible' => true,
        'is_featured' => true,
        'category_name' => 'Tecnología',
        'brand_name' => 'TechOwO',
        'images' => [
            ['image_path' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46', 'is_default' => true],
        ],
    ]);
});

test('GET / renders central homepage with initial data of stores and products', function () {
    $response = $this->get('http://localhost/');

    $response->assertStatus(200);
});

test('GET /api/central/marketplace/home-data returns featured stores, products and categories', function () {
    $response = $this->getJson('http://localhost/api/central/marketplace/home-data');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.featured_stores.0.id', $this->tenant1->id)
        ->assertJsonPath('data.featured_products.0.name', 'Camiseta Oversize Streetwear')
        ->assertJsonStructure([
            'status',
            'data' => [
                'featured_stores',
                'featured_products',
                'recent_products',
                'categories',
            ],
        ]);
});

test('GET /api/central/marketplace/products filters products by search, category and store', function () {
    // 1. All products
    $allResponse = $this->getJson('http://localhost/api/central/marketplace/products');
    $allResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(2, 'data.products');

    // 2. Filter by search "Mouse"
    $searchResponse = $this->getJson('http://localhost/api/central/marketplace/products?search=Mouse');
    $searchResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.products.0.name', 'Mouse Gamer Inalámbrico RGB');

    // 3. Filter by Category "Moda y Ropa"
    $catResponse = $this->getJson('http://localhost/api/central/marketplace/products?category=Moda y Ropa');
    $catResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.products.0.name', 'Camiseta Oversize Streetwear');

    // 4. Filter by Tenant ID
    $tenantResponse = $this->getJson("http://localhost/api/central/marketplace/products?tenant_id={$this->tenant2->id}");
    $tenantResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.products.0.tenant_id', $this->tenant2->id);
});

test('GET /api/central/marketplace/product/{slug} returns product detail with store info and related products', function () {
    $response = $this->getJson("http://localhost/api/central/marketplace/product/{$this->prod1->slug}");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.product.name', 'Camiseta Oversize Streetwear')
        ->assertJsonPath('data.product.price', 25)
        ->assertJsonPath('data.store.id', $this->tenant1->id)
        ->assertJsonPath('data.store.name', 'Tienda Ropa Urbana');
});

test('GET /api/central/marketplace/stores returns list of active tenant stores', function () {
    $response = $this->getJson('http://localhost/api/central/marketplace/stores');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'name', 'slug', 'domain', 'description'],
            ],
        ]);
});

test('Central web routes /marketplace, /cart and /checkout render 200 OK', function () {
    $this->get('http://localhost/marketplace')->assertStatus(200);
    $this->get('http://localhost/cart')->assertStatus(200);
    $this->get('http://localhost/checkout')->assertStatus(200);
    $this->get("http://localhost/product/{$this->prod1->slug}")->assertStatus(200);
});
