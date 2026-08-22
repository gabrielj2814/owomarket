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

    $migration = require base_path('database/migrations/tenant/2025_10_28_143000_create_brands.php');
    if (! Schema::hasTable('brands')) {
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

test('POST /api-tenant/brand/create creates a new brand and returns 201', function () {
    $payload = [
        'name' => 'Sony',
        'slug' => 'sony',
        'description' => 'Dispositivos de entretenimiento',
        'logo' => 'https://example.com/sony.png',
        'is_active' => true,
        'position' => 1,
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/brand/create", $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'code' => 201,
            'message' => 'Marca creada exitosamente',
            'data' => [
                'name' => 'Sony',
                'slug' => 'sony',
                'is_active' => true,
            ],
        ]);
});

test('POST /api-tenant/brand/create returns 422 on validation failure', function () {
    $payload = [
        'name' => 'S', // too short (< 2)
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/brand/create", $payload);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'code' => 422,
        ]);
});

test('POST /api-tenant/brand/filter returns paginated brands', function () {
    $this->postJson("http://{$this->domain}/api-tenant/brand/create", [
        'name' => 'Apple',
        'slug' => 'apple',
        'is_active' => true,
        'position' => 0,
    ]);

    $this->postJson("http://{$this->domain}/api-tenant/brand/create", [
        'name' => 'Microsoft',
        'slug' => 'microsoft',
        'is_active' => true,
        'position' => 1,
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/brand/filter", [
        'search' => 'Apple',
        'prePage' => 10,
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

test('GET /api-tenant/brand/{id} returns existing brand', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/brand/create", [
        'name' => 'Nintendo',
        'slug' => 'nintendo',
        'is_active' => true,
        'position' => 0,
    ])->json('data');

    $brandId = $created['id'];

    $response = $this->getJson("http://{$this->domain}/api-tenant/brand/{$brandId}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $brandId,
                'name' => 'Nintendo',
                'slug' => 'nintendo',
            ],
        ]);
});

test('PUT /api-tenant/brand/{id} updates existing brand', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/brand/create", [
        'name' => 'Logitech Antiguo',
        'slug' => 'logitech-antiguo',
        'is_active' => true,
        'position' => 0,
    ])->json('data');

    $brandId = $created['id'];

    $payload = [
        'name' => 'Logitech G',
        'slug' => 'logitech-g',
        'description' => 'Periféricos gaming',
        'logo' => 'https://example.com/logi.png',
        'is_active' => false,
        'position' => 3,
    ];

    $response = $this->putJson("http://{$this->domain}/api-tenant/brand/{$brandId}", $payload);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Marca actualizada exitosamente',
            'data' => [
                'id' => $brandId,
                'name' => 'Logitech G',
                'slug' => 'logitech-g',
                'is_active' => false,
                'position' => 3,
            ],
        ]);
});

test('DELETE /api-tenant/brand/{id} deletes brand', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/brand/create", [
        'name' => 'Marca a borrar',
        'slug' => 'marca-a-borrar',
        'is_active' => true,
    ])->json('data');

    $brandId = $created['id'];

    $response = $this->deleteJson("http://{$this->domain}/api-tenant/brand/{$brandId}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Marca eliminada exitosamente',
        ]);
});

test('GET /api-tenant/brand/active returns all active brands', function () {
    $this->postJson("http://{$this->domain}/api-tenant/brand/create", [
        'name' => 'Asus',
        'slug' => 'asus',
        'is_active' => true,
        'position' => 1,
    ]);

    $this->postJson("http://{$this->domain}/api-tenant/brand/create", [
        'name' => 'Acer',
        'slug' => 'acer',
        'is_active' => false,
        'position' => 2,
    ]);

    $response = $this->getJson("http://{$this->domain}/api-tenant/brand/active");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'is_active',
                ],
            ],
        ]);
});
