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

    $migration = require base_path('database/migrations/tenant/2025_10_28_145148_create_tax_rates.php');
    if (! Schema::hasTable('tax_rates')) {
        $migration->up();
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
    $this->tenantUser = actingAsTenantOwner();
});

test('POST /api-tenant/tax/create creates a tax rate and returns 201', function () {
    $payload = [
        'name' => 'IVA 16%',
        'rate' => 16.0,
        'country' => 'MX',
        'priority' => 1,
        'is_active' => true,
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/tax/create", $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'code' => 201,
            'message' => 'Tasa de impuesto creada exitosamente',
            'data' => [
                'name' => 'IVA 16%',
                'rate' => 16.0,
                'country' => 'MX',
            ],
        ]);
});

test('POST /api-tenant/tax/create returns 422 on invalid data', function () {
    $payload = [
        'name' => '',
        'rate' => 150.0,
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/tax/create", $payload);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'code' => 422,
        ]);
});

test('POST /api-tenant/tax/filter returns paginated tax rates', function () {
    $this->postJson("http://{$this->domain}/api-tenant/tax/create", [
        'name' => 'IVA 19%',
        'rate' => 19.0,
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/tax/filter", [
        'search' => 'IVA',
        'prePage' => 10,
        'page' => 1,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'code',
            'message',
            'data',
            'pagination',
        ]);
});

test('GET /api-tenant/tax/{id}, PUT /api-tenant/tax/{id} and DELETE /api-tenant/tax/{id} work as expected', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/tax/create", [
        'name' => 'IVA Test',
        'rate' => 10.0,
    ]);
    $id = $created->json('data.id');

    // GET
    $getResp = $this->getJson("http://{$this->domain}/api-tenant/tax/{$id}");
    $getResp->assertStatus(200)->assertJson(['data' => ['id' => $id]]);

    // PUT
    $putResp = $this->putJson("http://{$this->domain}/api-tenant/tax/{$id}", [
        'name' => 'IVA Test Modificado',
        'rate' => 12.0,
    ]);
    $putResp->assertStatus(200)->assertJson(['data' => ['name' => 'IVA Test Modificado', 'rate' => 12.0]]);

    // DELETE
    $deleteResp = $this->deleteJson("http://{$this->domain}/api-tenant/tax/{$id}");
    $deleteResp->assertStatus(200);
});

test('POST /api-tenant/tax/calculate calculates tax on subtotal', function () {
    $this->postJson("http://{$this->domain}/api-tenant/tax/create", [
        'name' => 'IVA MX',
        'rate' => 16.0,
        'country' => 'MX',
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/tax/calculate", [
        'subtotal' => 200.0,
        'country' => 'MX',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'subtotal' => 200.0,
                'total_tax' => 32.0,
                'total_with_tax' => 232.0,
            ],
        ]);
});
