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
        'name' => 'Settings API Store',
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

it('GET /api-tenant/settings returns initial store settings structure with defaults', function () {
    $response = $this->getJson("http://{$this->domain}/api-tenant/settings");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.grouped.general.store_name', 'Mi Tienda Online')
        ->assertJsonPath('data.grouped.general.currency', 'USD')
        ->assertJsonPath('data.flat.store_name', 'Mi Tienda Online');
});

it('PUT /api-tenant/settings updates full store settings across all groups', function () {
    $payload = [
        'store_name' => 'Mega Store Pro',
        'store_email' => 'contacto@megastore.cl',
        'currency' => 'CLP',
        'contact_phone' => '+56911223344',
        'address' => 'Ahumada 456, Santiago',
        'logo_url' => 'https://megastore.cl/logo.svg',
        'banner_url' => 'https://megastore.cl/hero.jpg',
        'social_facebook' => 'https://fb.com/megastore',
        'social_instagram' => 'https://ig.com/megastore',
        'social_whatsapp' => '+56911223344',
        'social_twitter' => 'https://x.com/megastore',
        'seo_title' => 'Mega Store - Lo mejor en electrónica',
        'seo_description' => 'Envíos a todo Chile en 24 horas',
        'seo_keywords' => 'tienda, electronica, chile',
    ];

    $response = $this->putJson("http://{$this->domain}/api-tenant/settings", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.grouped.general.store_name', 'Mega Store Pro')
        ->assertJsonPath('data.grouped.general.currency', 'CLP')
        ->assertJsonPath('data.grouped.appearance.logo_url', 'https://megastore.cl/logo.svg')
        ->assertJsonPath('data.grouped.social.instagram', 'https://ig.com/megastore')
        ->assertJsonPath('data.grouped.seo.meta_title', 'Mega Store - Lo mejor en electrónica')
        ->assertJsonPath('data.flat.store_name', 'Mega Store Pro');
});

it('PUT /api-tenant/settings returns 422 on invalid email format', function () {
    $response = $this->putJson("http://{$this->domain}/api-tenant/settings", [
        'store_email' => 'invalid-email-not-valid',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonStructure(['errors']);
});

it('POST /api-tenant/settings/item creates single setting and GET /api-tenant/settings/item/{key} retrieves it', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/settings/item", [
        'key' => 'maintenance_mode',
        'value' => '1',
        'type' => 'boolean',
        'group' => 'general',
    ]);

    $createRes->assertStatus(200)
        ->assertJsonPath('data.key', 'maintenance_mode')
        ->assertJsonPath('data.typed_value', true);

    $getRes = $this->getJson("http://{$this->domain}/api-tenant/settings/item/maintenance_mode");
    $getRes->assertStatus(200)
        ->assertJsonPath('data.key', 'maintenance_mode')
        ->assertJsonPath('data.typed_value', true);
});

it('GET /api-tenant/settings/item/{key} returns 404 for nonexistent key', function () {
    $response = $this->getJson("http://{$this->domain}/api-tenant/settings/item/nonexistent_setting_key");
    $response->assertStatus(404)
        ->assertJsonPath('status', 'error');
});

it('GET /api-tenant/settings/group/{group} lists settings in that group and DELETE /api-tenant/settings/item/{key} removes it', function () {
    $this->postJson("http://{$this->domain}/api-tenant/settings/item", [
        'key' => 'footer_text',
        'value' => 'Todos los derechos reservados',
        'type' => 'string',
        'group' => 'appearance',
    ]);

    $groupRes = $this->getJson("http://{$this->domain}/api-tenant/settings/group/appearance");
    $groupRes->assertStatus(200)
        ->assertJsonCount(1, 'data');

    $deleteRes = $this->deleteJson("http://{$this->domain}/api-tenant/settings/item/footer_text");
    $deleteRes->assertStatus(200)
        ->assertJsonPath('status', 'success');

    $getAfterDelete = $this->getJson("http://{$this->domain}/api-tenant/settings/item/footer_text");
    $getAfterDelete->assertStatus(404);
});
