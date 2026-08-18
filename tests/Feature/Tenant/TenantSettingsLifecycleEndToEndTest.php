<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    if (! Schema::hasTable('tenant_settings')) {
        (require base_path('database/migrations/tenant/2025_10_28_144914_create_tenant_settings.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Settings E2E Store',
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

it('executes full tenant store settings lifecycle end-to-end', function () {
    // 1. Initial State: Returns default values
    $initialRes = $this->getJson("http://{$this->domain}/api-tenant/settings");
    $initialRes->assertStatus(200)
        ->assertJsonPath('data.grouped.general.store_name', 'Mi Tienda Online')
        ->assertJsonPath('data.grouped.general.currency', 'USD')
        ->assertJsonPath('data.grouped.appearance.logo_url', null);

    // 2. Full Store Configuration Update
    $updatePayload = [
        'store_name' => 'OwO Store Santiago',
        'store_email' => 'ventas@owostore.cl',
        'currency' => 'CLP',
        'contact_phone' => '+56987654321',
        'address' => 'Costanera Center, Nivel 3, Providencia',
        'logo_url' => 'https://cdn.owostore.cl/images/logo.png',
        'banner_url' => 'https://cdn.owostore.cl/images/banner-cyber.webp',
        'social_facebook' => 'https://facebook.com/owostorecl',
        'social_instagram' => 'https://instagram.com/owostorecl',
        'social_whatsapp' => '+56987654321',
        'social_twitter' => 'https://x.com/owostorecl',
        'seo_title' => 'OwO Store Santiago - Especialistas en Gaming y Hardware',
        'seo_description' => 'Encuentra las mejores marcas de computación y periféricos con despacho express.',
        'seo_keywords' => 'gaming, laptops, hardware, rtx, chile, santiago',
    ];

    $updateRes = $this->putJson("http://{$this->domain}/api-tenant/settings", $updatePayload);
    $updateRes->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.grouped.general.store_name', 'OwO Store Santiago')
        ->assertJsonPath('data.grouped.general.currency', 'CLP')
        ->assertJsonPath('data.grouped.appearance.logo_url', 'https://cdn.owostore.cl/images/logo.png')
        ->assertJsonPath('data.grouped.social.instagram', 'https://instagram.com/owostorecl')
        ->assertJsonPath('data.grouped.seo.meta_title', 'OwO Store Santiago - Especialistas en Gaming y Hardware')
        ->assertJsonPath('data.flat.currency', 'CLP');

    // 3. Verify Database Persistence on fresh GET
    $getUpdatedRes = $this->getJson("http://{$this->domain}/api-tenant/settings");
    $getUpdatedRes->assertStatus(200)
        ->assertJsonPath('data.grouped.general.store_name', 'OwO Store Santiago')
        ->assertJsonPath('data.grouped.general.store_email', 'ventas@owostore.cl')
        ->assertJsonPath('data.grouped.general.address', 'Costanera Center, Nivel 3, Providencia')
        ->assertJsonPath('data.grouped.appearance.banner_url', 'https://cdn.owostore.cl/images/banner-cyber.webp')
        ->assertJsonPath('data.grouped.social.whatsapp', '+56987654321');

    // 4. Query group endpoints
    $appearanceGroup = $this->getJson("http://{$this->domain}/api-tenant/settings/group/appearance");
    $appearanceGroup->assertStatus(200)
        ->assertJsonCount(2, 'data');

    $socialGroup = $this->getJson("http://{$this->domain}/api-tenant/settings/group/social");
    $socialGroup->assertStatus(200)
        ->assertJsonCount(4, 'data');

    // 5. Create Custom Setting Item
    $createItemRes = $this->postJson("http://{$this->domain}/api-tenant/settings/item", [
        'key' => 'free_shipping_min_amount',
        'value' => '50000',
        'type' => 'integer',
        'group' => 'general',
    ]);
    $createItemRes->assertStatus(200)
        ->assertJsonPath('data.key', 'free_shipping_min_amount')
        ->assertJsonPath('data.typed_value', 50000);

    // 6. Consult individual setting
    $getItemRes = $this->getJson("http://{$this->domain}/api-tenant/settings/item/free_shipping_min_amount");
    $getItemRes->assertStatus(200)
        ->assertJsonPath('data.typed_value', 50000)
        ->assertJsonPath('data.type', 'integer');

    // 7. Update individual setting
    $updateItemRes = $this->postJson("http://{$this->domain}/api-tenant/settings/item", [
        'key' => 'free_shipping_min_amount',
        'value' => '75000',
        'type' => 'integer',
        'group' => 'general',
    ]);
    $updateItemRes->assertStatus(200)
        ->assertJsonPath('data.typed_value', 75000);

    // 8. Delete individual setting
    $deleteItemRes = $this->deleteJson("http://{$this->domain}/api-tenant/settings/item/free_shipping_min_amount");
    $deleteItemRes->assertStatus(200);

    $getAfterDelete = $this->getJson("http://{$this->domain}/api-tenant/settings/item/free_shipping_min_amount");
    $getAfterDelete->assertStatus(404);

    // 9. Final check: Core store settings remain preserved
    $finalCheck = $this->getJson("http://{$this->domain}/api-tenant/settings");
    $finalCheck->assertStatus(200)
        ->assertJsonPath('data.grouped.general.store_name', 'OwO Store Santiago')
        ->assertJsonPath('data.grouped.general.currency', 'CLP');
});
