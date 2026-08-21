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

    $migration1 = require base_path('database/migrations/tenant/2025_10_28_143325_create_product_attributes.php');
    if (! Schema::hasTable('product_attributes')) {
        $migration1->up();
    }

    $migration2 = require base_path('database/migrations/tenant/2025_10_28_143921_create_product_attribute_values.php');
    if (! Schema::hasTable('product_attribute_values')) {
        $migration2->up();
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
    $this->tenantUser = \Src\Tenant\Infrastructure\Eloquent\Models\User::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'name' => 'Store Staff',
        'email' => 'staff_'.bin2hex(random_bytes(5)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);
    $this->actingAs($this->tenantUser);
});

test('POST /api-tenant/attribute/create creates a new attribute and returns 201', function () {
    $payload = [
        'name' => 'Color Primario',
        'slug' => 'color-primario',
        'type' => 'color',
        'is_filterable' => true,
        'is_visible' => true,
        'position' => 1,
        'values' => [
            ['value' => 'Rojo', 'color' => '#FF0000', 'position' => 1],
            ['value' => 'Azul', 'color' => '#0000FF', 'position' => 2],
        ],
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/attribute/create", $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'code' => 201,
            'message' => 'Atributo creado exitosamente',
            'data' => [
                'name' => 'Color Primario',
                'slug' => 'color-primario',
                'type' => 'color',
                'is_filterable' => true,
            ],
        ]);
});

test('POST /api-tenant/attribute/create returns 422 on validation failure', function () {
    $payload = [
        'name' => '',
        'type' => 'invalid_type',
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/attribute/create", $payload);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'code' => 422,
        ]);
});

test('POST /api-tenant/attribute/filter returns paginated attributes', function () {
    $this->postJson("http://{$this->domain}/api-tenant/attribute/create", [
        'name' => 'Talla',
        'slug' => 'talla',
        'type' => 'button',
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/attribute/filter", [
        'search' => 'Talla',
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
        ]);
});

test('GET /api-tenant/attribute/{id} returns existing attribute', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/attribute/create", [
        'name' => 'Material',
        'slug' => 'material',
        'type' => 'select',
    ]);

    $id = $created->json('data.id');

    $response = $this->getJson("http://{$this->domain}/api-tenant/attribute/{$id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $id,
                'name' => 'Material',
            ],
        ]);
});

test('PUT /api-tenant/attribute/{id} updates existing attribute', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/attribute/create", [
        'name' => 'Estilo',
        'slug' => 'estilo',
        'type' => 'select',
    ]);

    $id = $created->json('data.id');

    $response = $this->putJson("http://{$this->domain}/api-tenant/attribute/{$id}", [
        'name' => 'Estilo y Corte',
        'slug' => 'estilo-y-corte',
        'type' => 'button',
        'is_filterable' => true,
        'position' => 3,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $id,
                'name' => 'Estilo y Corte',
                'slug' => 'estilo-y-corte',
                'type' => 'button',
            ],
        ]);
});

test('DELETE /api-tenant/attribute/{id} deletes attribute', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/attribute/create", [
        'name' => 'Eliminar Attr',
        'slug' => 'eliminar-attr',
    ]);

    $id = $created->json('data.id');

    $response = $this->deleteJson("http://{$this->domain}/api-tenant/attribute/{$id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Atributo eliminado exitosamente',
        ]);
});

test('POST /api-tenant/attribute/{attributeId}/values and DELETE /api-tenant/attribute/values/{valueId} manage values', function () {
    $createdAttr = $this->postJson("http://{$this->domain}/api-tenant/attribute/create", [
        'name' => 'Acabado',
        'slug' => 'acabado',
    ]);

    $attrId = $createdAttr->json('data.id');

    $createdVal = $this->postJson("http://{$this->domain}/api-tenant/attribute/{$attrId}/values", [
        'value' => 'Mate',
        'position' => 1,
    ]);

    $createdVal->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'value' => 'Mate',
            ],
        ]);

    $valId = $createdVal->json('data.id');

    $deleteVal = $this->deleteJson("http://{$this->domain}/api-tenant/attribute/values/{$valId}");

    $deleteVal->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Valor de atributo eliminado exitosamente',
        ]);
});

test('GET /api-tenant/attribute/with-values returns all visible attributes with their values', function () {
    $response = $this->getJson("http://{$this->domain}/api-tenant/attribute/with-values");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'code',
            'message',
            'data',
        ]);
});
