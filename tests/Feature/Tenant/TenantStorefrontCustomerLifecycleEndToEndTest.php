<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Src\Brand\Infrastructure\Eloquent\Models\Brand;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Coupon\Infrastructure\Eloquent\Models\Coupon;
use Src\Customer\Infrastructure\Eloquent\Models\Customer;
use Src\Order\Infrastructure\Eloquent\Models\Order;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Product\Infrastructure\Eloquent\Models\ProductImage;
use Src\Product\Infrastructure\Eloquent\Models\ProductVariant;
use Src\Review\Infrastructure\Eloquent\Models\ProductReview;
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
    if (! Schema::hasTable('product_reviews')) {
        (require base_path('database/migrations/tenant/2025_10_28_144615_create_product_reviews.php'))->up();
    }
    // Fase 1.3 (hallazgo C1): el registro del pago dejó de llevar un
    // `catch (\Throwable)` vacío, así que la tabla `payments` tiene que
    // existir de verdad. Antes su ausencia pasaba inadvertida.
    if (! Schema::hasTable('payments')) {
        (require base_path('database/migrations/tenant/2025_10_28_144517_create_payments.php'))->up();
    }
    if (! Schema::hasTable('coupons')) {
        (require base_path('database/migrations/tenant/2025_10_28_144655_create_coupons.php'))->up();
    }
    if (! Schema::hasTable('orders')) {
        (require base_path('database/migrations/tenant/2025_10_28_144320_create_orders.php'))->up();
    }
    if (! Schema::hasColumn('orders', 'coupon_code')) {
        (require base_path('database/migrations/tenant/2026_08_19_000011_add_coupon_code_to_orders_table.php'))->up();
    }
    if (! Schema::hasTable('order_items')) {
        (require base_path('database/migrations/tenant/2025_10_28_144403_create_order_items.php'))->up();
    }
    if (! Schema::hasTable('tenant_settings')) {
        (require base_path('database/migrations/tenant/2025_10_28_144914_create_tenant_settings.php'))->up();
    }
    if (! Schema::hasColumn('products', 'category_id')) {
        (require base_path('database/migrations/tenant/2026_08_18_000004_add_category_and_brand_to_products_table.php'))->up();
    }

    // Fase 4: la columna donde el pedido guarda la tasa a la que compro el cliente. Este
    // fichero construye el esquema del inquilino a mano, asi que la migracion aditiva hay que
    // pedirla aqui igual que se hizo con `coupon_code`.
    if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'exchange_rate')) {
        (require base_path('database/migrations/tenant/2026_08_30_130000_add_exchange_rate_to_orders_table.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Storefront E2E Store',
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
    $this->tenantUser = actingAsTenantOwner();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('executes full storefront customer lifecycle from browsing to order confirmation', function () {
    // 1. Setup Store Settings via API
    $this->putJson("http://{$this->domain}/api-tenant/settings", [
        'store_name' => 'Baymax Deportes Online',
        'currency' => 'USD',
        'store_email' => 'contacto@baymaxstore.com',
    ]);

    // 2. Setup Category and Brand
    $category = Category::create([
        'name' => 'Calzado Deportivo',
        'slug' => 'calzado-deportivo',
        'description' => 'Zapatillas deportivas para running y entrenamiento',
        'is_active' => true,
        'position' => 1,
    ]);

    $brand = Brand::create([
        'name' => 'Nike Athletics',
        'slug' => 'nike-athletics',
        'is_active' => true,
    ]);

    // 3. Setup Featured Product with Variant and Image
    $product = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Zapatillas Nike Air Zoom Pegasus 40',
        'slug' => 'zapatillas-nike-air-zoom-pegasus-40',
        'sku' => 'NIKE-PEGASUS-40',
        'description' => 'Zapatillas running de alto rendimiento con amortiguación React.',
        'price' => 129.99,
        'compare_price' => 159.99,
        'quantity' => 20,
        'is_featured' => true,
        'is_visible' => true,
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'specifications' => ['Material' => 'Malla transpirable', 'Suela' => 'Goma waffle'],
    ]);

    ProductImage::create([
        'id' => (string) Str::uuid(),
        'product_id' => $product->id,
        'image_path' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff',
        'order' => 0,
        'is_default' => true,
    ]);

    $variant = ProductVariant::create([
        'id' => (string) Str::uuid(),
        'product_id' => $product->id,
        'sku' => 'NIKE-PEGASUS-40-BLK-41',
        'price' => 129.99,
        'quantity' => 8,
        'attributes' => ['Color' => 'Negro', 'Talla' => '41'],
    ]);

    // 4. Setup Discount Coupon
    Coupon::create([
        'id' => (string) Str::uuid(),
        'code' => 'BIENVENIDA10',
        'type' => 'percentage',
        'value' => 10.00,
        'min_order_amount' => 50.00,
        'usage_limit' => 100,
        'used_count' => 0,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addMonths(1),
        'is_active' => true,
    ]);

    // 5. Setup Customer and Approved Review
    $customer = Customer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Matías Valenzuela',
        'email' => 'matias.valenzuela@test.com',
        'phone' => '+56911223344',
        'is_active' => true,
    ]);

    ProductReview::create([
        'id' => (string) Str::uuid(),
        'product_id' => $product->id,
        'customer_id' => $customer->id,
        'rating' => 5,
        'title' => '¡Comodidad absoluta!',
        'comment' => 'Excelente calce y amortiguación para trotar en asfalto.',
        'response' => '¡Gracias Matías por tu compra!',
        'responded_at' => now(),
        'is_approved' => true,
        'is_verified' => true,
    ]);

    // =========================================================================
    // STEP 1: Home Page Request (/)
    // =========================================================================
    $homeResponse = $this->get("http://{$this->domain}/");
    $homeResponse->assertStatus(200);
    $homeResponse->assertInertia(fn (Assert $page) => $page
        ->component('marketplace/home/TenantStorefrontHomePage')
        ->has('categories', 1)
        ->has('featured_products', 1)
        ->where('store_settings.store_name', 'Baymax Deportes Online')
    );

    // =========================================================================
    // STEP 2: Catalog Page Request (/catalog)
    // =========================================================================
    $catalogResponse = $this->get("http://{$this->domain}/catalog?search=Pegasus&category=calzado-deportivo");
    $catalogResponse->assertStatus(200);
    $catalogResponse->assertInertia(fn (Assert $page) => $page
        ->component('marketplace/catalog/TenantCatalogPage')
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Zapatillas Nike Air Zoom Pegasus 40')
    );

    // =========================================================================
    // STEP 3: Product Detail Page (/product/{slug})
    // =========================================================================
    $productDetailResponse = $this->get("http://{$this->domain}/product/{$product->slug}");
    $productDetailResponse->assertStatus(200);
    $productDetailResponse->assertInertia(fn (Assert $page) => $page
        ->component('marketplace/product/TenantProductDetailPage')
        ->where('product.name', 'Zapatillas Nike Air Zoom Pegasus 40')
        ->has('product.variants', 1)
        ->has('reviews', 1)
        ->where('reviews.0.author_name', 'Matías Valenzuela')
    );

    // =========================================================================
    // STEP 4: Shopping Cart & Coupon Validation (/cart & /api-tenant/coupon/validate)
    // =========================================================================
    $cartResponse = $this->get("http://{$this->domain}/cart");
    $cartResponse->assertStatus(200);
    $cartResponse->assertInertia(fn (Assert $page) => $page
        ->component('marketplace/cart/TenantCartPage')
    );

    $couponValidationResponse = $this->postJson("http://{$this->domain}/api-tenant/coupon/validate", [
        'code' => 'BIENVENIDA10',
        'order_subtotal' => 129.99,
    ]);
    $couponValidationResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.is_valid', true)
        ->assertJsonPath('data.discount_amount', 13); // 10% of 129.99 rounded

    // =========================================================================
    // STEP 5: Checkout Page (/checkout)
    // =========================================================================
    $checkoutResponse = $this->get("http://{$this->domain}/checkout");
    $checkoutResponse->assertStatus(200);
    $checkoutResponse->assertInertia(fn (Assert $page) => $page
        ->component('marketplace/checkout/TenantCheckoutPage')
        ->has('shipping_methods')
        ->has('payment_methods')
    );

    // =========================================================================
    // STEP 6: Place Storefront Order (/checkout/create-order)
    // =========================================================================
    $orderPayload = [
        'customer' => [
            'name' => 'Matías Valenzuela',
            'email' => 'matias.valenzuela@test.com',
            'phone' => '+56911223344',
            'document_id' => '18.123.456-7',
        ],
        'shipping_address' => [
            'address' => 'Av. Providencia 1234, Depto 502',
            'city' => 'Santiago',
            'state' => 'Región Metropolitana',
            'zip' => '7500000',
            'notes' => 'Dejar en conserjería',
        ],
        'shipping_method' => 'standard',
        'shipping_amount' => 5.00,
        'payment_method' => 'bank_transfer',
        'coupon_code' => 'BIENVENIDA10',
        'items' => [
            [
                'product_id' => (string) $product->id,
                'product_name' => $product->name,
                'sku' => 'NIKE-PEGASUS-40-BLK-41',
                'price' => 129.99,
                'quantity' => 1,
                'variant_id' => (string) $variant->id,
                'attributes' => ['Color' => 'Negro', 'Talla' => '41'],
            ],
        ],
    ];

    $orderCreateResponse = $this->postJson("http://{$this->domain}/checkout/create-order", $orderPayload);
    $orderCreateResponse->assertStatus(201)
        ->assertJsonPath('status', 'success');

    $createdOrderId = $orderCreateResponse->json('data.order_id');
    expect($createdOrderId)->not->toBeNull();

    // Verify order in database
    $createdOrder = Order::with('items')->find($createdOrderId);
    expect($createdOrder)->not->toBeNull()
        ->and($createdOrder->subtotal)->toBe(129.99)
        ->and($createdOrder->shipping_amount)->toBe(5.0)
        ->and($createdOrder->discount_amount)->toBe(13.0)
        ->and($createdOrder->total)->toBe(121.99)
        ->and($createdOrder->items)->toHaveCount(1);

    // Verify variant stock decremented.
    //
    // Este assert cambió en la Fase 0.4: antes esperaba que el producto padre
    // bajara también a 19, porque el checkout descontaba DOS VECES la misma
    // unidad (de la variante y del producto). Era parte del hallazgo C1 y el
    // test lo daba por bueno. Ahora, si la línea trae variante, sólo se
    // descuenta de la variante.
    expect((int) $variant->fresh()->quantity)->toBe(7)
        ->and((int) $product->fresh()->quantity)->toBe(20);

    // =========================================================================
    // STEP 7: Order Confirmation Page (/order/{id}/confirmation)
    // =========================================================================
    $confirmationResponse = $this->get("http://{$this->domain}/order/{$createdOrderId}/confirmation");
    $confirmationResponse->assertStatus(200);
    $confirmationResponse->assertInertia(fn (Assert $page) => $page
        ->component('marketplace/checkout/TenantOrderConfirmationPage')
        ->where('order.id', (string) $createdOrderId)
        ->where('order.order_number', $createdOrder->order_number)
        ->where('order.customer.name', 'Matías Valenzuela')
        ->has('order.items', 1)
    );
});

it('displays only approved customer reviews on product detail page', function () {
    $product = Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Polerón Hoodie Casual',
        'slug' => 'poleron-hoodie-casual',
        'sku' => 'POL-HOODIE-01',
        'price' => 49.99,
        'quantity' => 15,
        'is_visible' => true,
    ]);

    $cust1 = Customer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Cliente Aprobado',
        'email' => 'aprobado@test.com',
        'is_active' => true,
    ]);

    $cust2 = Customer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Cliente Pendiente',
        'email' => 'pendiente@test.com',
        'is_active' => true,
    ]);

    // 1. Approved Review
    ProductReview::create([
        'id' => (string) Str::uuid(),
        'product_id' => $product->id,
        'customer_id' => $cust1->id,
        'rating' => 5,
        'title' => 'Excelente Calidad',
        'comment' => 'El algodón es muy suave y la talla es perfecta.',
        'is_approved' => true,
        'is_verified' => true,
    ]);

    // 2. Unapproved Review (pending moderation)
    ProductReview::create([
        'id' => (string) Str::uuid(),
        'product_id' => $product->id,
        'customer_id' => $cust2->id,
        'rating' => 1,
        'title' => 'Pendiente de Aprobación',
        'comment' => 'Comentario no aprobado aún.',
        'is_approved' => false,
        'is_verified' => false,
    ]);

    $response = $this->get("http://{$this->domain}/product/{$product->slug}");
    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('marketplace/product/TenantProductDetailPage')
        ->has('reviews', 1)
        ->where('reviews.0.author_name', 'Cliente Aprobado')
        ->where('reviews.0.title', 'Excelente Calidad')
        ->where('reviews_summary.total_reviews', 1)
        ->where('reviews_summary.avg_rating', 5)
    );
});
