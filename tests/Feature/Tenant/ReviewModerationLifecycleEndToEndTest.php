<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Brand\Infrastructure\Eloquent\Models\Brand as EloquentBrand;
use Src\Category\Infrastructure\Eloquent\Models\Category as EloquentCategory;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as EloquentCustomer;
use Src\Order\Infrastructure\Eloquent\Models\Order as EloquentOrder;
use Src\Order\Infrastructure\Eloquent\Models\OrderItem as EloquentOrderItem;
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
    // Hallazgo B2: la insignia de "compra verificada" ahora comprueba que el
    // pedido contenga el producto reseñado, así que hace falta order_items.
    // Y order_items tiene una clave foránea a product_variants, de modo que
    // esa tabla debe existir antes.
    if (! Schema::hasTable('product_variants')) {
        (require base_path('database/migrations/tenant/2025_10_28_143954_create_product_variants.php'))->up();
    }
    if (! Schema::hasTable('order_items')) {
        (require base_path('database/migrations/tenant/2025_10_28_144403_create_order_items.php'))->up();
    }
    if (! Schema::hasTable('product_reviews')) {
        (require base_path('database/migrations/tenant/2025_10_28_144615_create_product_reviews.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Review E2E Store',
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

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('executes full product review moderation and rating lifecycle end-to-end', function () {
    // 1. Initial State: Summary has 0 reviews
    $initialSummary = $this->getJson("http://{$this->domain}/api-tenant/review/summary");
    $initialSummary->assertStatus(200)
        ->assertJsonPath('data.total_reviews', 0)
        ->assertJsonPath('data.average_rating', 0);

    // 2. Setup Category, Brand, Product and Customers
    $category = EloquentCategory::create([
        'name' => 'Computación',
        'slug' => 'computacion-'.bin2hex(random_bytes(4)),
        'is_active' => true,
    ]);

    $brand = EloquentBrand::create([
        'name' => 'ASUS',
        'slug' => 'asus-'.bin2hex(random_bytes(4)),
        'is_active' => true,
    ]);

    $product = EloquentProduct::create([
        'id' => (string) Str::uuid(),
        'name' => 'ASUS ROG Zephyrus G14',
        'slug' => 'asus-rog-zephyrus-g14-'.bin2hex(random_bytes(4)),
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'sku' => 'ROG-G14-'.bin2hex(random_bytes(2)),
        'price' => 1899.99,
        'is_visible' => true,
    ]);

    $customer1 = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Alexis Sanchez',
        'email' => 'alexis@maravilla.cl',
        'is_active' => true,
    ]);

    $customer2 = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Arturo Vidal',
        'email' => 'arturo@king.cl',
        'is_active' => true,
    ]);

    $order2 = EloquentOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-REV-002',
        'customer_id' => $customer2->id,
        'status' => 'delivered',
        'payment_status' => 'paid',
        'payment_method' => 'webpay',
        'currency' => 'USD',
        'subtotal' => 1899.99,
        'total' => 1899.99,
    ]);

    // Hallazgo B2: la insignia de "compra verificada" ahora exige que el
    // pedido contenga realmente el producto reseñado, así que el pedido de
    // prueba necesita su línea. Antes bastaba con que el order_id existiera.
    EloquentOrderItem::create([
        'id' => (string) Str::uuid(),
        'order_id' => $order2->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'sku' => $product->sku,
        'price' => 1899.99,
        'quantity' => 1,
        'total' => 1899.99,
    ]);

    // 3. Customer 1 writes a 5-star review (pending moderation, is_approved = false)
    $create1Res = $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $product->id,
        'customer_id' => $customer1->id,
        'rating' => 5,
        'title' => 'Potencia increíble para gaming',
        'comment' => 'Corre todo en ultra sin problemas de temperatura.',
        'is_approved' => false,
    ]);

    $create1Res->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.is_approved', false);

    $review1Id = $create1Res->json('data.id');

    // Verify unapproved reviews do not affect public product rating summary
    $summaryBeforeApprove = $this->getJson("http://{$this->domain}/api-tenant/review/summary/{$product->id}");
    $summaryBeforeApprove->assertStatus(200)
        ->assertJsonPath('data.total_reviews', 0)
        ->assertJsonPath('data.average_rating', 0);

    // 4. Admin moderates and approves review 1
    $moderate1Res = $this->postJson("http://{$this->domain}/api-tenant/review/{$review1Id}/moderate", [
        'is_approved' => true,
    ]);
    $moderate1Res->assertStatus(200)
        ->assertJsonPath('data.is_approved', true);

    // Verify summary is now 1 review, 5.0 stars
    $summaryAfterApprove1 = $this->getJson("http://{$this->domain}/api-tenant/review/summary/{$product->id}");
    $summaryAfterApprove1->assertStatus(200)
        ->assertJsonPath('data.total_reviews', 1)
        ->assertJsonPath('data.average_rating', 5)
        ->assertJsonPath('data.star_breakdown.5', 1);

    // 5. Customer 2 writes a 3-star review with verified purchase order
    $create2Res = $this->postJson("http://{$this->domain}/api-tenant/review/create", [
        'product_id' => $product->id,
        'customer_id' => $customer2->id,
        'order_id' => $order2->id,
        'rating' => 3,
        'title' => 'Buen rendimiento pero ventiladores ruidosos',
        'comment' => 'Suena bastante al jugar juegos pesados.',
    ]);

    // is_verified sale en true porque el pedido es de este cliente y contiene
    // el producto; is_approved sigue en false hasta que el comerciante modere.
    $create2Res->assertStatus(201)
        ->assertJsonPath('data.is_verified', true)
        ->assertJsonPath('data.is_approved', false)
        ->assertJsonPath('data.rating', 3);

    $review2Id = $create2Res->json('data.id');

    // Como ninguna reseña nace aprobada, hace falta moderarla también para que
    // entre en el resumen público.
    $this->postJson("http://{$this->domain}/api-tenant/review/{$review2Id}/moderate", [
        'is_approved' => true,
    ])->assertStatus(200);

    // Summary calculation: 5 and 3 -> avg = 4.0
    $summary2Reviews = $this->getJson("http://{$this->domain}/api-tenant/review/summary/{$product->id}");
    $summary2Reviews->assertStatus(200)
        ->assertJsonPath('data.total_reviews', 2)
        ->assertJsonPath('data.average_rating', 4)
        ->assertJsonPath('data.star_breakdown.5', 1)
        ->assertJsonPath('data.star_breakdown.3', 1);

    // 6. Admin answers review 2 with official store reply
    $respondRes = $this->postJson("http://{$this->domain}/api-tenant/review/{$review2Id}/respond", [
        'response' => 'Hola Arturo, te recomendamos configurar el modo Silent en el software Armoury Crate.',
    ]);
    $respondRes->assertStatus(200)
        ->assertJsonPath('data.response', 'Hola Arturo, te recomendamos configurar el modo Silent en el software Armoury Crate.')
        ->assertJsonPath('data.responded_at', fn ($dt) => ! empty($dt));

    // 7. Customer 2 edits review after following store advice, upgrades to 5 stars
    $updateRes = $this->putJson("http://{$this->domain}/api-tenant/review/{$review2Id}", [
        'rating' => 5,
        'title' => 'Actualizado: Silencioso y potente!',
        'comment' => 'El soporte me explicó el perfil de ventiladores y ahora va de maravilla.',
    ]);
    $updateRes->assertStatus(200)
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.title', 'Actualizado: Silencioso y potente!');

    // Summary calculation now: 5 and 5 -> avg = 5.0
    $summaryAfterUpdate = $this->getJson("http://{$this->domain}/api-tenant/review/summary/{$product->id}");
    $summaryAfterUpdate->assertStatus(200)
        ->assertJsonPath('data.total_reviews', 2)
        ->assertJsonPath('data.average_rating', 5)
        ->assertJsonPath('data.star_breakdown.5', 2)
        ->assertJsonPath('data.star_breakdown.3', 0);

    // 8. Filter reviews by text search
    $filterRes = $this->postJson("http://{$this->domain}/api-tenant/review/filter", [
        'search' => 'Arturo',
        'has_response' => true,
    ]);
    $filterRes->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $review2Id);

    // 9. Delete review 1
    $deleteRes = $this->deleteJson("http://{$this->domain}/api-tenant/review/{$review1Id}");
    $deleteRes->assertStatus(200);

    // Final Summary after deletion: 1 review, 5.0 stars
    $finalSummary = $this->getJson("http://{$this->domain}/api-tenant/review/summary/{$product->id}");
    $finalSummary->assertStatus(200)
        ->assertJsonPath('data.total_reviews', 1)
        ->assertJsonPath('data.average_rating', 5)
        ->assertJsonPath('data.star_breakdown.5', 1);
});
