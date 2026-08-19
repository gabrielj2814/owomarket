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

    $migration = require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php');
    if (! Schema::hasTable('categories')) {
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
});

test('POST /api-tenant/category/create creates a new category and returns 201', function () {
    $payload = [
        'name' => 'Electrónica',
        'slug' => 'electronica',
        'description' => 'Todo sobre electrónica',
        'is_active' => true,
        'position' => 1,
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/category/create", $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'code' => 201,
            'message' => 'Categoría creada exitosamente',
            'data' => [
                'name' => 'Electrónica',
                'slug' => 'electronica',
                'is_active' => true,
            ],
        ]);
});

test('POST /api-tenant/category/create returns 422 on validation failure', function () {
    $payload = [
        'name' => 'A', // too short (< 2)
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/category/create", $payload);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'code' => 422,
        ]);
});

test('POST /api-tenant/category/filter returns paginated categories', function () {
    $this->postJson("http://{$this->domain}/api-tenant/category/create", [
        'name' => 'Libros',
        'slug' => 'libros',
        'is_active' => true,
        'position' => 0,
    ]);

    $this->postJson("http://{$this->domain}/api-tenant/category/create", [
        'name' => 'Música',
        'slug' => 'musica',
        'is_active' => true,
        'position' => 1,
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/category/filter", [
        'search' => 'Libros',
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

test('GET /api-tenant/category/{id} returns existing category', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/category/create", [
        'name' => 'Videojuegos',
        'slug' => 'videojuegos',
        'is_active' => true,
        'position' => 0,
    ])->json('data');

    $categoryId = $created['id'];

    $response = $this->getJson("http://{$this->domain}/api-tenant/category/{$categoryId}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $categoryId,
                'name' => 'Videojuegos',
                'slug' => 'videojuegos',
            ],
        ]);
});

test('PUT /api-tenant/category/{id} updates existing category', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/category/create", [
        'name' => 'Deportes Antiguo',
        'slug' => 'deportes-antiguo',
        'is_active' => true,
        'position' => 0,
    ])->json('data');

    $categoryId = $created['id'];

    $payload = [
        'name' => 'Deportes y Fitness',
        'slug' => 'deportes-fitness',
        'description' => 'Artículos deportivos',
        'is_active' => false,
        'position' => 2,
    ];

    $response = $this->putJson("http://{$this->domain}/api-tenant/category/{$categoryId}", $payload);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Categoría actualizada exitosamente',
            'data' => [
                'id' => $categoryId,
                'name' => 'Deportes y Fitness',
                'slug' => 'deportes-fitness',
                'is_active' => false,
                'position' => 2,
            ],
        ]);
});

test('DELETE /api-tenant/category/{id} deletes category', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/category/create", [
        'name' => 'Categoría a borrar',
        'slug' => 'categoria-a-borrar',
        'is_active' => true,
    ])->json('data');

    $categoryId = $created['id'];

    $response = $this->deleteJson("http://{$this->domain}/api-tenant/category/{$categoryId}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Categoría eliminada exitosamente',
        ]);
});

test('GET /api-tenant/category/tree returns hierarchical tree', function () {
    $parent = $this->postJson("http://{$this->domain}/api-tenant/category/create", [
        'name' => 'Mujer',
        'slug' => 'mujer',
        'is_active' => true,
    ])->json('data');

    $this->postJson("http://{$this->domain}/api-tenant/category/create", [
        'name' => 'Vestidos',
        'slug' => 'vestidos',
        'parent_id' => $parent['id'],
        'is_active' => true,
    ]);

    $response = $this->getJson("http://{$this->domain}/api-tenant/category/tree");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'children',
                ],
            ],
        ]);
});
