<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
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
    if (! Schema::hasTable('product_attributes')) {
        (require base_path('database/migrations/tenant/2025_10_28_143325_create_product_attributes.php'))->up();
    }
    if (! Schema::hasTable('product_attribute_values')) {
        (require base_path('database/migrations/tenant/2025_10_28_143921_create_product_attribute_values.php'))->up();
    }
    if (! Schema::hasTable('product_variants')) {
        (require base_path('database/migrations/tenant/2025_10_28_143954_create_product_variants.php'))->up();
    }
    if (! Schema::hasTable('product_variant_attribute_values')) {
        (require base_path('database/migrations/tenant/2025_10_28_144041_create_product_variant_attribute_values.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Test',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'domain' => $this->domain,
    ]);

    // Fase 0.3-E: /api-tenant/* dejó de estar abierto (hallazgo A5). Las rutas
    // de backoffice exigen ahora sesión de usuario de la tienda; se autentica
    // aquí para todo el archivo.
    $this->tenantUser = \Src\Tenant\Infrastructure\Eloquent\Models\User::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'name' => 'Store Staff',
        'email' => 'staff_'.bin2hex(random_bytes(5)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);
    $this->actingAs($this->tenantUser);
});

test('POST /api-tenant/product/create creates product with images and variants and returns 201', function () {
    $payload = [
        'name' => 'Laptop Gamer ASUS ROG',
        'slug' => 'laptop-gamer-asus-rog',
        'sku' => 'ASUS-ROG-001',
        'price' => 1499.99,
        'compare_price' => 1799.99,
        'cost_price' => 1100.00,
        'quantity' => 10,
        'min_quantity' => 1,
        'max_quantity' => 20,
        'is_visible' => true,
        'is_featured' => true,
        'description' => 'Laptop de alto rendimiento',
        'images' => [
            [
                'image_path' => 'https://example.com/laptop1.jpg',
                'alt_text' => 'Foto frontal',
                'is_default' => true,
                'order' => 0,
            ],
        ],
        'variants' => [
            [
                'sku' => 'ASUS-ROG-32GB',
                'price' => 1699.99,
                'quantity' => 5,
                'attributes' => ['RAM' => '32GB'],
            ],
        ],
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/product/create", $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'code' => 201,
            'message' => 'Producto creado exitosamente',
            'data' => [
                'name' => 'Laptop Gamer ASUS ROG',
                'slug' => 'laptop-gamer-asus-rog',
                'sku' => 'ASUS-ROG-001',
                'price' => 1499.99,
                'compare_price' => 1799.99,
                'is_visible' => true,
                'is_featured' => true,
            ],
        ]);
});

test('POST /api-tenant/product/create returns 422 on validation failure', function () {
    $payload = [
        'name' => 'L', // too short (< 2)
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/product/create", $payload);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'code' => 422,
        ]);
});

test('POST /api-tenant/product/filter returns paginated products', function () {
    $this->postJson("http://{$this->domain}/api-tenant/product/create", [
        'name' => 'Teclado Gamer',
        'slug' => 'teclado-gamer',
        'sku' => 'TEC-GAM-01',
        'price' => 50.00,
        'quantity' => 15,
    ]);

    $this->postJson("http://{$this->domain}/api-tenant/product/create", [
        'name' => 'Mouse Gamer',
        'slug' => 'mouse-gamer',
        'sku' => 'MOU-GAM-01',
        'price' => 30.00,
        'quantity' => 20,
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/product/filter", [
        'search' => 'Teclado',
        'per_page' => 10,
        'page' => 1,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'code',
            'message',
            'data',
            'pagination' => [
                'total',
                'current_page',
                'per_page',
                'last_page',
            ],
        ])
        ->assertJson([
            'status' => 'success',
            'pagination' => [
                'total' => 1,
            ],
        ]);
});

test('POST /api-tenant/product/filter with null boolean filters returns all active products without erroneous filtering', function () {
    $this->postJson("http://{$this->domain}/api-tenant/product/create", [
        'name' => 'Audífonos Bluetooth',
        'slug' => 'audifonos-bluetooth-' . bin2hex(random_bytes(3)),
        'sku' => 'AUD-BT-' . bin2hex(random_bytes(2)),
        'price' => 80.00,
        'quantity' => 10,
        'is_visible' => true,
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/product/filter", [
        'search' => null,
        'category_id' => null,
        'brand_id' => null,
        'is_visible' => null,
        'in_stock' => null,
        'is_featured' => null,
        'is_digital' => null,
        'page' => 1,
        'per_page' => 10,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'code' => 200,
        ]);

    expect($response->json('pagination.total'))->toBeGreaterThanOrEqual(1);
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(1);
});

test('GET /api-tenant/product/{id} returns existing product by id and slug', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/product/create", [
        'name' => 'Impresora Multifuncional',
        'slug' => 'impresora-multifuncional',
        'sku' => 'IMP-MUL-01',
        'price' => 200.00,
        'quantity' => 5,
    ])->json('data');

    $productId = $created['id'];

    // Consult by UUID
    $resById = $this->getJson("http://{$this->domain}/api-tenant/product/{$productId}");
    $resById->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $productId,
                'name' => 'Impresora Multifuncional',
            ],
        ]);

    // Consult by Slug
    $resBySlug = $this->getJson("http://{$this->domain}/api-tenant/product/impresora-multifuncional");
    $resBySlug->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $productId,
                'slug' => 'impresora-multifuncional',
            ],
        ]);
});

test('PUT /api-tenant/product/{id} updates existing product', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/product/create", [
        'name' => 'Webcam 720p',
        'slug' => 'webcam-720p',
        'sku' => 'CAM-720-01',
        'price' => 25.00,
        'quantity' => 8,
    ])->json('data');

    $productId = $created['id'];

    $payload = [
        'name' => 'Webcam 1080p Full HD',
        'slug' => 'webcam-1080p-full-hd',
        'sku' => 'CAM-1080-01',
        'price' => 45.00,
        'quantity' => 12,
        'is_featured' => true,
    ];

    $response = $this->putJson("http://{$this->domain}/api-tenant/product/{$productId}", $payload);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Producto actualizado exitosamente',
            'data' => [
                'id' => $productId,
                'name' => 'Webcam 1080p Full HD',
                'sku' => 'CAM-1080-01',
                'price' => 45.00,
                'quantity' => 12,
                'is_featured' => true,
            ],
        ]);
});

test('DELETE /api-tenant/product/{id} deletes product', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/product/create", [
        'name' => 'Producto Descontinuado',
        'slug' => 'producto-descontinuado',
        'sku' => 'DESC-001',
        'price' => 1.00,
        'quantity' => 1,
    ])->json('data');

    $productId = $created['id'];

    $response = $this->deleteJson("http://{$this->domain}/api-tenant/product/{$productId}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Producto eliminado exitosamente',
        ]);
});

test('PATCH /api-tenant/product/{id}/toggle-visibility and PATCH /api-tenant/product/{id}/stock', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/product/create", [
        'name' => 'Tablet Mini',
        'slug' => 'tablet-mini',
        'sku' => 'TAB-MINI-01',
        'price' => 120.00,
        'quantity' => 4,
        'is_visible' => true,
    ])->json('data');

    $productId = $created['id'];

    $toggleRes = $this->patchJson("http://{$this->domain}/api-tenant/product/{$productId}/toggle-visibility", [
        'is_visible' => false,
    ]);
    $toggleRes->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Visibilidad del producto actualizada exitosamente',
        ]);

    $stockRes = $this->patchJson("http://{$this->domain}/api-tenant/product/{$productId}/stock", [
        'quantity' => 50,
    ]);
    $stockRes->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Stock del producto actualizado exitosamente',
        ]);

    $check = $this->getJson("http://{$this->domain}/api-tenant/product/{$productId}")->json('data');
    expect($check['is_visible'])->toBeFalse()
        ->and($check['quantity'])->toBe(50);
});
