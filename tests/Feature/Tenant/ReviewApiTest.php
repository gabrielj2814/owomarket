<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Brand\Infrastructure\Eloquent\Models\Brand as EloquentBrand;
use Src\Category\Infrastructure\Eloquent\Models\Category as EloquentCategory;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as EloquentCustomer;
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

    if (! Schema::hasTable('categories')) {
        (require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php'))->up();
    }
    if (! Schema::hasTable('brands')) {
        (require base_path('database/migrations/tenant/2025_10_28_143000_create_brands.php'))->up();
    }
    if (! Schema::hasTable('products')) {
        (require base_path('database/migrations/tenant/2025_10_28_143038_create_products.php'))->up();
    }
    if (! Schema::hasTable('customers')) {
        (require base_path('database/migrations/tenant/2025_10_28_144201_create_customers.php'))->up();
    }
    if (! Schema::hasTable('orders')) {
        (require base_path('database/migrations/tenant/2025_10_28_144320_create_orders.php'))->up();
    }
    if (! Schema::hasTable('product_reviews')) {
        (require base_path('database/migrations/tenant/2025_10_28_144615_create_product_reviews.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Review API Test Store',
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

    $this->category = EloquentCategory::create([
        'name' => 'Smartphones',
        'slug' => 'smartphones-'.bin2hex(random_bytes(4)),
        'is_active' => true,
    ]);

    $this->brand = EloquentBrand::create([
        'name' => 'Apple',
        'slug' => 'apple-'.bin2hex(random_bytes(4)),
        'is_active' => true,
    ]);

    $this->product = EloquentProduct::create([
        'id' => (string) Str::uuid(),
        'name' => 'iPhone 15 Pro Max',
        'slug' => 'iphone-15-pro-max-'.bin2hex(random_bytes(4)),
        'category_id' => $this->category->id,
        'brand_id' => $this->brand->id,
        'sku' => 'IPHONE15-PRO-'.bin2hex(random_bytes(2)),
        'price' => 1299.00,
        'is_visible' => true,
    ]);

    $this->customer = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Carolina Arregui',
        'email' => 'carolina@actores.cl',
        'is_active' => true,
    ]);

    // Fase 0.3-E: /api-tenant/* dejó de estar abierto (hallazgo A5). Las rutas
    // de backoffice exigen ahora sesión de usuario de la tienda; se autentica
    // aquí para todo el archivo.
    $this->tenantUser = actingAsTenantOwner();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('GET /api-tenant/review/summary returns initial summary', function () {
    $response = $this->getJson("http://{$this->domain}/api-tenant/review/summary");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.total_reviews', 0)
        ->assertJsonPath('data.average_rating', 0);
});

it('POST /api-tenant/review/create creates a review and returns 201', function () {
    // Hallazgo B2: se envía 'is_approved' => true a propósito. Debe ignorarse:
    // la reseña nace pendiente de moderación, la apruebe quien la apruebe.
    $response = $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $this->product->id,
        'customer_id' => $this->customer->id,
        'rating' => 5,
        'title' => 'Increíble pantalla y cámara',
        'comment' => 'Muy superior a modelos anteriores, 100% recomendado.',
        'is_approved' => true,
        'is_verified' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.product_id', $this->product->id)
        ->assertJsonPath('data.customer_id', $this->customer->id)
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.title', 'Increíble pantalla y cámara')
        ->assertJsonPath('data.is_approved', false)
        ->assertJsonPath('data.is_verified', false);
});

it('POST /api-tenant/review/create returns 422 on invalid rating or missing fields', function () {
    $response = $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $this->product->id,
        'customer_id' => $this->customer->id,
        'rating' => 6, // Invalid > 5
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonStructure(['errors']);
});

it('POST /api-tenant/review/create returns 409 on duplicate review', function () {
    $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $this->product->id,
        'customer_id' => $this->customer->id,
        'rating' => 5,
    ]);

    $duplicate = $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $this->product->id,
        'customer_id' => $this->customer->id,
        'rating' => 4,
    ]);

    $duplicate->assertStatus(409)
        ->assertJsonPath('status', 'error');
});

it('GET /api-tenant/review/{id} retrieves existing review', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $this->product->id,
        'customer_id' => $this->customer->id,
        'rating' => 4,
        'title' => 'Buen rendimiento',
    ]);
    $reviewId = $createRes->json('data.id');

    $getRes = $this->getJson("http://{$this->domain}/api-tenant/review/{$reviewId}");
    $getRes->assertStatus(200)
        ->assertJsonPath('data.id', $reviewId)
        ->assertJsonPath('data.rating', 4);
});

it('POST /api-tenant/review/{id}/moderate updates approval status', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $this->product->id,
        'customer_id' => $this->customer->id,
        'rating' => 3,
        'is_approved' => false,
    ]);
    $reviewId = $createRes->json('data.id');

    $moderateRes = $this->postJson("http://{$this->domain}/api-tenant/review/{$reviewId}/moderate", [
        'is_approved' => true,
    ]);

    $moderateRes->assertStatus(200)
        ->assertJsonPath('data.is_approved', true);
});

it('POST /api-tenant/review/{id}/respond adds merchant reply', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $this->product->id,
        'customer_id' => $this->customer->id,
        'rating' => 4,
        'comment' => 'Buen producto pero demoró el envío.',
    ]);
    $reviewId = $createRes->json('data.id');

    $respondRes = $this->postJson("http://{$this->domain}/api-tenant/review/{$reviewId}/respond", [
        'response' => 'Hola Carolina, nos alegra que te guste. Mejoraremos los tiempos de entrega!',
    ]);

    $respondRes->assertStatus(200)
        ->assertJsonPath('data.response', 'Hola Carolina, nos alegra que te guste. Mejoraremos los tiempos de entrega!')
        ->assertJsonPath('data.responded_at', fn ($v) => ! empty($v));
});

it('PUT /api-tenant/review/{id} updates review content and DELETE /api-tenant/review/{id} removes it', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $this->product->id,
        'customer_id' => $this->customer->id,
        'rating' => 2,
        'title' => 'Dudoso',
    ]);
    $reviewId = $createRes->json('data.id');

    $putRes = $this->putJson("http://{$this->domain}/api-tenant/review/{$reviewId}", [
        'rating' => 5,
        'title' => 'Rectificado: Excelente',
        'comment' => 'El soporte me explicó la configuración y quedó genial.',
    ]);
    $putRes->assertStatus(200)
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.title', 'Rectificado: Excelente');

    $deleteRes = $this->deleteJson("http://{$this->domain}/api-tenant/review/{$reviewId}");
    $deleteRes->assertStatus(200);

    $getAfterDelete = $this->getJson("http://{$this->domain}/api-tenant/review/{$reviewId}");
    $getAfterDelete->assertStatus(404);
});

it('POST /api-tenant/review/filter filters reviews with search and pagination', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $this->product->id,
        'customer_id' => $this->customer->id,
        'rating' => 5,
        'title' => 'Excelente compra',
    ]);

    // Ninguna reseña nace aprobada (hallazgo B2): hay que moderarla para que
    // el filtro por is_approved = true la encuentre.
    $this->postJson("http://{$this->domain}/api-tenant/review/{$createRes->json('data.id')}/moderate", [
        'is_approved' => true,
    ])->assertStatus(200);

    $filterRes = $this->postJson("http://{$this->domain}/api-tenant/review/filter", [
        'rating' => 5,
        'is_approved' => true,
        'per_page' => 10,
        'page' => 1,
    ]);

    $filterRes->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('data.0.rating', 5);
});
