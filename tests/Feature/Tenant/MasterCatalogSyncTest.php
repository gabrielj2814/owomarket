<?php

declare(strict_types=1);

use Src\Brand\Infrastructure\Eloquent\Models\CentralBrand;
use Src\Category\Infrastructure\Eloquent\Models\CentralCategory;
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

test('POST /api-tenant/brand/sync-central synchronizes central master brands into tenant database with central_uuid', function () {
    $centralUuid1 = (string) Str::uuid();
    $centralUuid2 = (string) Str::uuid();
    $name1 = 'Brand Alpha ' . bin2hex(random_bytes(3));
    $name2 = 'Brand Beta ' . bin2hex(random_bytes(3));
    $slug1 = 'brand-alpha-' . bin2hex(random_bytes(4));
    $slug2 = 'brand-beta-' . bin2hex(random_bytes(4));

    CentralBrand::create([
        'id' => $centralUuid1,
        'name' => $name1,
        'slug' => $slug1,
        'logo' => 'https://example.com/alpha.png',
        'description' => 'Alpha devices',
        'is_active' => true,
        'position' => 1,
    ]);

    CentralBrand::create([
        'id' => $centralUuid2,
        'name' => $name2,
        'slug' => $slug2,
        'logo' => 'https://example.com/beta.png',
        'description' => 'Beta computers',
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
    expect($tenantBrand1->name)->toBe($name1);
    expect($tenantBrand1->slug)->toBe($slug1);

    expect($tenantBrand2)->not->toBeNull();
    expect($tenantBrand2->name)->toBe($name2);
    expect($tenantBrand2->slug)->toBe($slug2);
});

test('POST /api-tenant/category/sync-central synchronizes hierarchical central categories into tenant database with central_uuid and parent_id', function () {
    $parentUuid = (string) Str::uuid();
    $childUuid = (string) Str::uuid();
    $parentName = 'Parent Cat ' . bin2hex(random_bytes(3));
    $childName = 'Child Cat ' . bin2hex(random_bytes(3));
    $parentSlug = 'parent-cat-' . bin2hex(random_bytes(4));
    $childSlug = 'child-cat-' . bin2hex(random_bytes(4));

    CentralCategory::create([
        'id' => $parentUuid,
        'name' => $parentName,
        'slug' => $parentSlug,
        'icon' => 'LuCpu',
        'image' => 'https://example.com/parent.png',
        'description' => 'Parent category description',
        'parent_id' => null,
        'is_active' => true,
        'position' => 1,
    ]);

    CentralCategory::create([
        'id' => $childUuid,
        'name' => $childName,
        'slug' => $childSlug,
        'icon' => 'LuSmartphone',
        'image' => 'https://example.com/child.png',
        'description' => 'Child category description',
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
    expect($parentTenantCat->name)->toBe($parentName);
    expect($parentTenantCat->parent_id)->toBeNull();

    expect($childTenantCat)->not->toBeNull();
    expect($childTenantCat->name)->toBe($childName);
    expect($childTenantCat->parent_id)->toBe($parentTenantCat->id);
});

test('sync updates existing tenant brands by slug or name without creating duplicates', function () {
    $centralUuid = (string) Str::uuid();
    $name = 'Shoes Brand ' . bin2hex(random_bytes(3));
    $slug = 'shoes-brand-' . bin2hex(random_bytes(4));

    // Existing local brand created previously without central_uuid
    $localBrand = Brand::create([
        'name' => $name,
        'slug' => $slug,
        'description' => 'Local description',
        'is_active' => true,
        'position' => 0,
    ]);

    // Central master brand with updated description and logo
    CentralBrand::create([
        'id' => $centralUuid,
        'name' => $name,
        'slug' => $slug,
        'logo' => 'https://example.com/shoes.png',
        'description' => 'Updated Central description',
        'is_active' => true,
        'position' => 10,
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/brand/sync-central");

    $response->assertStatus(200);

    // Verify no duplicates were created
    $matchingBrands = Brand::where('slug', $slug)->get();
    expect($matchingBrands->count())->toBe(1);

    $updatedBrand = $matchingBrands->first();
    expect($updatedBrand->id)->toBe($localBrand->id);
    expect($updatedBrand->central_uuid)->toBe($centralUuid);
    expect($updatedBrand->description)->toBe('Updated Central description');
    expect($updatedBrand->logo)->toBe('https://example.com/shoes.png');
    expect($updatedBrand->position)->toBe(10);
});

test('sync is fully idempotent on multiple executions', function () {
    $centralUuid = (string) Str::uuid();
    $name = 'Electronics ' . bin2hex(random_bytes(3));
    $slug = 'electronics-' . bin2hex(random_bytes(4));

    CentralBrand::create([
        'id' => $centralUuid,
        'name' => $name,
        'slug' => $slug,
        'logo' => 'https://example.com/elec.png',
        'is_active' => true,
        'position' => 5,
    ]);

    // Run first sync
    $res1 = $this->postJson("http://{$this->domain}/api-tenant/brand/sync-central");
    $res1->assertStatus(200);

    $countAfterFirst = Brand::where('central_uuid', $centralUuid)->count();
    expect($countAfterFirst)->toBe(1);

    // Run second sync immediately
    $res2 = $this->postJson("http://{$this->domain}/api-tenant/brand/sync-central");
    $res2->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'created_count' => 0,
            ],
        ]);

    $countAfterSecond = Brand::where('central_uuid', $centralUuid)->count();
    expect($countAfterSecond)->toBe(1);
});
