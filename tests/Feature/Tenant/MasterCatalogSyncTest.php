<?php

declare(strict_types=1);

use App\Models\CentralBrand;
use App\Models\CentralCategory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Brand\Infrastructure\Eloquent\Models\Brand;
use Src\Category\Infrastructure\Eloquent\Models\Category;
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

    // Ensure central migrations exist in test database
    if (! Schema::hasTable('tenant_categories')) {
        $catMigration = require base_path('database/migrations/2025_10_28_181831_create_tenant_categories.php');
        $catMigration->up();
    }
    if (! Schema::hasTable('central_brands')) {
        $brandMigration = require base_path('database/migrations/2026_08_18_100000_create_central_brands_table.php');
        $brandMigration->up();
    }

    // Ensure tenant tables exist
    if (! Schema::hasTable('categories')) {
        $catTenantMigration = require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php');
        $catTenantMigration->up();
    }
    if (! Schema::hasTable('brands')) {
        $brandTenantMigration = require base_path('database/migrations/tenant/2025_10_28_143000_create_brands.php');
        $brandTenantMigration->up();
    }
    if (! Schema::hasColumn('categories', 'central_uuid') || ! Schema::hasColumn('brands', 'central_uuid')) {
        $uuidTenantMigration = require base_path('database/migrations/tenant/2026_08_18_000005_add_central_uuid_to_categories_and_brands.php');
        $uuidTenantMigration->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Test Sync',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);
});

test('POST /api-tenant/brand/sync-central synchronizes central master brands into tenant database with central_uuid', function () {
    $centralUuid1 = (string) Str::uuid();
    $centralUuid2 = (string) Str::uuid();
    $slug1 = 'sony-corp-' . bin2hex(random_bytes(4));
    $slug2 = 'apple-inc-' . bin2hex(random_bytes(4));

    CentralBrand::create([
        'id' => $centralUuid1,
        'name' => 'Sony Corporation',
        'slug' => $slug1,
        'logo' => 'https://example.com/sony.png',
        'description' => 'Dispositivos de entretenimiento',
        'is_active' => true,
        'position' => 1,
    ]);

    CentralBrand::create([
        'id' => $centralUuid2,
        'name' => 'Apple Inc',
        'slug' => $slug2,
        'logo' => 'https://example.com/apple.png',
        'description' => 'Smartphones y computadoras',
        'is_active' => true,
        'position' => 2,
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/brand/sync-central");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'code' => 200,
        ]);

    $tenantBrand1 = Brand::where('central_uuid', $centralUuid1)->first();
    $tenantBrand2 = Brand::where('central_uuid', $centralUuid2)->first();

    expect($tenantBrand1)->not->toBeNull();
    expect($tenantBrand1->name)->toBe('Sony Corporation');
    expect($tenantBrand1->slug)->toBe($slug1);

    expect($tenantBrand2)->not->toBeNull();
    expect($tenantBrand2->name)->toBe('Apple Inc');
    expect($tenantBrand2->slug)->toBe($slug2);
});

test('POST /api-tenant/category/sync-central synchronizes hierarchical central categories into tenant database with central_uuid and parent_id', function () {
    $parentUuid = (string) Str::uuid();
    $childUuid = (string) Str::uuid();
    $parentSlug = 'tecnologia-' . bin2hex(random_bytes(4));
    $childSlug = 'smartphones-' . bin2hex(random_bytes(4));

    CentralCategory::create([
        'id' => $parentUuid,
        'name' => 'Tecnología',
        'slug' => $parentSlug,
        'icon' => 'LuCpu',
        'image' => 'https://example.com/tech.png',
        'description' => 'Categoría raíz de tecnología',
        'parent_id' => null,
        'is_active' => true,
        'position' => 1,
    ]);

    CentralCategory::create([
        'id' => $childUuid,
        'name' => 'Smartphones',
        'slug' => $childSlug,
        'icon' => 'LuSmartphone',
        'image' => 'https://example.com/phones.png',
        'description' => 'Teléfonos móviles',
        'parent_id' => $parentUuid,
        'is_active' => true,
        'position' => 1,
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/category/sync-central");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'code' => 200,
        ]);

    $parentTenantCat = Category::where('central_uuid', $parentUuid)->first();
    $childTenantCat = Category::where('central_uuid', $childUuid)->first();

    expect($parentTenantCat)->not->toBeNull();
    expect($parentTenantCat->name)->toBe('Tecnología');
    expect($parentTenantCat->parent_id)->toBeNull();

    expect($childTenantCat)->not->toBeNull();
    expect($childTenantCat->name)->toBe('Smartphones');
    expect($childTenantCat->parent_id)->toBe($parentTenantCat->id);
});
