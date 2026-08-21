<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Src\Brand\Infrastructure\Eloquent\Models\CentralBrand;
use Src\Category\Infrastructure\Eloquent\Models\CentralCategory;
use Src\Admin\Infrastructure\Eloquent\Models\CentralHomeBanner;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Src\User\Infrastructure\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Tests\TestCase;

class AdminPhaseThreeOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Event::fake([
            \Stancl\Tenancy\Events\TenantCreated::class,
            \Stancl\Tenancy\Events\TenantDeleted::class,
        ]);

        config([
            'tenancy.bootstrappers' => array_values(array_filter(
                config('tenancy.bootstrappers', []),
                fn ($bootstrapper) => $bootstrapper !== \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class
            )),
        ]);

        $this->superAdmin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Super Admin OwOMarket',
            'email' => 'admin.phase3@owomarket.local',
            'password' => bcrypt('password123'),
            'type' => 'super_admin',
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'id' => 'techstore',
            'name' => 'Tech Store C.A.',
            'status' => 'active',
            'request' => 'approved',
        ]);
    }

    public function test_super_admin_can_manage_master_brands_catalog(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Crear marca maestra vía POST
        $responseCreate = $this->postJson('/admin/api/catalog/master-brands', [
            'name' => 'Samsung Global',
            'slug' => 'samsung-global',
            'logo' => 'https://owomarket.local/logos/samsung.png',
            'description' => 'Fabricante líder de tecnología',
            'position' => 1,
            'is_active' => true,
        ]);

        $responseCreate->assertStatus(200);
        $brandId = $responseCreate->json('data.id');
        $this->assertNotEmpty($brandId);
        $this->assertDatabaseHas('central_brands', [
            'name' => 'Samsung Global',
            'slug' => 'samsung-global',
        ]);

        // 2. Renderizar vista web
        $responseView = $this->get("/admin/backoffice/{$this->superAdmin->id}/catalog/master-brands");
        $responseView->assertStatus(200);
        $responseView->assertInertia(fn (Assert $page) => $page
            ->component('admin/catalog/AdminMasterBrandsPage')
            ->has('brands_data.data', 1)
            ->where('metrics.total_brands', 1)
        );

        // 3. Modificar marca
        $responseEdit = $this->postJson('/admin/api/catalog/master-brands', [
            'id' => $brandId,
            'name' => 'Samsung Electronics',
            'slug' => 'samsung-electronics',
            'position' => 2,
            'is_active' => false,
        ]);
        $responseEdit->assertStatus(200);
        $this->assertDatabaseHas('central_brands', [
            'id' => $brandId,
            'name' => 'Samsung Electronics',
            'is_active' => false,
        ]);

        // 4. Eliminar marca
        $responseDelete = $this->deleteJson("/admin/api/catalog/master-brands/{$brandId}");
        $responseDelete->assertStatus(200);
        $this->assertDatabaseMissing('central_brands', ['id' => $brandId]);
    }

    public function test_super_admin_can_manage_master_categories_tree(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Crear categoría raíz
        $responseRoot = $this->postJson('/admin/api/catalog/master-categories', [
            'name' => 'Tecnología',
            'slug' => 'tecnologia',
            'position' => 1,
            'is_active' => true,
        ]);
        $responseRoot->assertStatus(200);
        $rootId = $responseRoot->json('data.id');

        // 2. Crear subcategoría
        $responseSub = $this->postJson('/admin/api/catalog/master-categories', [
            'name' => 'Smartphones',
            'slug' => 'smartphones',
            'parent_id' => $rootId,
            'position' => 1,
            'is_active' => true,
        ]);
        $responseSub->assertStatus(200);
        $subId = $responseSub->json('data.id');

        // 3. Renderizar vista web
        $responseView = $this->get("/admin/backoffice/{$this->superAdmin->id}/catalog/master-categories");
        $responseView->assertStatus(200);
        $responseView->assertInertia(fn (Assert $page) => $page
            ->component('admin/catalog/AdminMasterCategoriesPage')
            ->where('metrics.total_categories', 2)
            ->where('metrics.root_categories', 1)
        );

        // 4. Listar API
        $responseApi = $this->getJson('/admin/api/catalog/master-categories');
        $responseApi->assertStatus(200);
        $this->assertCount(1, $responseApi->json('data.tree'));
        $this->assertCount(1, $responseApi->json('data.tree.0.children'));

        // 5. Eliminar categoría raíz
        $responseDelete = $this->deleteJson("/admin/api/catalog/master-categories/{$rootId}");
        $responseDelete->assertStatus(200);
        $this->assertDatabaseMissing('tenant_categories', ['id' => $rootId]);
    }

    public function test_super_admin_can_moderate_marketplace_products(): void
    {
        $this->actingAs($this->superAdmin);

        // Crear producto sincronizado en central
        $product = CentralProduct::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'tenant_product_id' => (string) Str::uuid(),
            'name' => 'Laptop Gamer Pro 16GB',
            'slug' => 'laptop-gamer-pro-16gb',
            'price' => 1250.00,
            'quantity' => 10,
            'is_visible' => false,
            'is_featured' => false,
            'category_name' => 'Computación',
            'brand_name' => 'Asus',
        ]);

        // 1. Renderizar vista de moderación
        $responseView = $this->get("/admin/backoffice/{$this->superAdmin->id}/catalog/moderation");
        $responseView->assertStatus(200);
        $responseView->assertInertia(fn (Assert $page) => $page
            ->component('admin/catalog/AdminProductsModerationPage')
            ->has('products_data.data', 1)
            ->where('metrics.pending_products', 1)
        );

        // 2. Moderar y Aprobar producto + Destacar + Comisión personalizada
        $responseModerate = $this->postJson("/admin/api/catalog/moderation-products/{$product->id}/moderate", [
            'is_visible' => true,
            'is_featured' => true,
            'commission_rate' => 7.5,
            'moderation_notes' => 'Producto verificado con alta calidad de imágenes.',
        ]);

        $responseModerate->assertStatus(200);
        $product->refresh();
        $this->assertTrue($product->is_visible);
        $this->assertTrue($product->is_featured);
        $this->assertSame(7.5, $product->metadata['custom_commission_rate']);
        $this->assertCount(1, $product->metadata['moderation_history']);
    }

    public function test_super_admin_can_manage_home_cms_banners(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Crear banner hero slider
        $responseCreate = $this->postJson('/admin/api/cms/home-banners', [
            'title' => 'Festival de Ofertas OwOMarket',
            'subtitle' => 'Descuentos de hasta 50% en todas las tiendas',
            'image_url' => 'https://owomarket.local/banners/promo1.jpg',
            'link_url' => '/catalog',
            'badge_text' => 'HOT SALE',
            'position_type' => 'hero_slider',
            'order_position' => 1,
            'is_active' => true,
        ]);

        $responseCreate->assertStatus(200);
        $bannerId = $responseCreate->json('data.id');
        $this->assertNotEmpty($bannerId);
        $this->assertDatabaseHas('central_home_banners', [
            'title' => 'Festival de Ofertas OwOMarket',
            'position_type' => 'hero_slider',
        ]);

        // 2. Renderizar vista web
        $responseView = $this->get("/admin/backoffice/{$this->superAdmin->id}/cms/banners");
        $responseView->assertStatus(200);
        $responseView->assertInertia(fn (Assert $page) => $page
            ->component('admin/cms/AdminHomeBannersPage')
            ->has('banners', 1)
            ->where('metrics.hero_sliders', 1)
        );

        // 3. Eliminar banner
        $responseDelete = $this->deleteJson("/admin/api/cms/home-banners/{$bannerId}");
        $responseDelete->assertStatus(200);
        $this->assertDatabaseMissing('central_home_banners', ['id' => $bannerId]);
    }

    public function test_super_admin_can_manage_b2b_subscription_plans(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Crear plan de suscripción
        $responseCreate = $this->postJson('/admin/api/plans/subscription-plans', [
            'name' => 'Plan Crecimiento Pro',
            'slug' => 'plan-crecimiento-pro',
            'description' => 'Para comercios medianos en expansión',
            'price_monthly' => 29.99,
            'price_yearly' => 299.90,
            'commission_rate' => 4.5,
            'max_products' => 500,
            'features' => ['Dominio propio', 'Soporte prioritario', 'BCV sync'],
            'is_active' => true,
        ]);

        $responseCreate->assertStatus(200);
        $planId = $responseCreate->json('data.id');
        $this->assertNotEmpty($planId);
        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'Plan Crecimiento Pro',
            'commission_rate' => 4.5,
        ]);

        // 2. Renderizar vista web
        $responseView = $this->get("/admin/backoffice/{$this->superAdmin->id}/plans");
        $responseView->assertStatus(200);
        $responseView->assertInertia(fn (Assert $page) => $page
            ->component('admin/plans/AdminSubscriptionPlansPage')
            ->has('plans', 1)
            ->where('metrics.total_plans', 1)
        );

        // 3. Editar plan
        $responseEdit = $this->postJson('/admin/api/plans/subscription-plans', [
            'id' => $planId,
            'name' => 'Plan Crecimiento Ultra Pro',
            'price_monthly' => 39.99,
            'commission_rate' => 3.5,
            'max_products' => 1000,
        ]);
        $responseEdit->assertStatus(200);
        $this->assertDatabaseHas('subscription_plans', [
            'id' => $planId,
            'name' => 'Plan Crecimiento Ultra Pro',
            'commission_rate' => 3.5,
        ]);

        // 4. Eliminar plan
        $responseDelete = $this->deleteJson("/admin/api/plans/subscription-plans/{$planId}");
        $responseDelete->assertStatus(200);
        $this->assertDatabaseMissing('subscription_plans', ['id' => $planId]);
    }
}
