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

    if (! Schema::hasTable('customers')) {
        (require base_path('database/migrations/tenant/2025_10_28_144201_create_customers.php'))->up();
    }
    if (! Schema::hasTable('addresses')) {
        (require base_path('database/migrations/tenant/2025_10_28_144231_create_addresses.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Customer API Test Store',
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

it('POST /api-tenant/customer/create creates customer and returns 201', function () {
    $payload = [
        'name' => 'Daniel Valenzuela',
        'email' => 'daniel@empresa.cl',
        'phone' => '+56911223344',
        'birth_date' => '1992-07-14',
        'gender' => 'male',
        'is_active' => true,
        'accepts_marketing' => true,
        'addresses' => [
            [
                'first_name' => 'Daniel',
                'last_name' => 'Valenzuela',
                'address_line_1' => 'Av. Suecia 200',
                'city' => 'Providencia',
                'state' => 'RM',
                'postal_code' => '7510000',
                'country' => 'Chile',
                'type' => 'shipping',
                'is_default' => true,
            ],
        ],
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/customer/create", $payload);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Daniel Valenzuela')
        ->assertJsonPath('data.email', 'daniel@empresa.cl')
        ->assertJsonCount(1, 'data.addresses');
});

it('POST /api-tenant/customer/create returns 422 on validation or duplicate email failure', function () {
    // 1. Error de validación básica
    $invalidResponse = $this->postJson("http://{$this->domain}/api-tenant/customer/create", [
        'name' => '',
        'email' => 'not-an-email',
    ]);
    $invalidResponse->assertStatus(422);

    // 2. Crear cliente válido
    $this->postJson("http://{$this->domain}/api-tenant/customer/create", [
        'name' => 'Primer Cliente',
        'email' => 'duplicado@empresa.cl',
    ])->assertStatus(201);

    // 3. Intentar crear con el mismo email
    $dupResponse = $this->postJson("http://{$this->domain}/api-tenant/customer/create", [
        'name' => 'Segundo Cliente',
        'email' => 'duplicado@empresa.cl',
    ]);
    $dupResponse->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

it('GET /api-tenant/customer/{id} and PUT /api-tenant/customer/{id} and DELETE /api-tenant/customer/{id}', function () {
    // 1. Crear cliente
    $create = $this->postJson("http://{$this->domain}/api-tenant/customer/create", [
        'name' => 'Felipe Perez',
        'email' => 'felipe@empresa.cl',
        'phone' => '+56900001111',
    ]);
    $customerId = $create->json('data.id');

    // 2. Consultar por ID
    $getRes = $this->getJson("http://{$this->domain}/api-tenant/customer/{$customerId}");
    $getRes->assertStatus(200)
        ->assertJsonPath('data.name', 'Felipe Perez');

    // 3. Consultar ID inexistente (404)
    $notFound = $this->getJson("http://{$this->domain}/api-tenant/customer/00000000-0000-0000-0000-000000000000");
    $notFound->assertStatus(404);

    // 4. Actualizar cliente
    $updateRes = $this->putJson("http://{$this->domain}/api-tenant/customer/{$customerId}", [
        'name' => 'Felipe Andres Perez',
        'email' => 'felipe.andres@empresa.cl',
        'phone' => '+56999998888',
        'is_active' => false,
    ]);
    $updateRes->assertStatus(200)
        ->assertJsonPath('data.name', 'Felipe Andres Perez')
        ->assertJsonPath('data.email', 'felipe.andres@empresa.cl')
        ->assertJsonPath('data.is_active', false);

    // 5. Eliminar cliente
    $delRes = $this->deleteJson("http://{$this->domain}/api-tenant/customer/{$customerId}");
    $delRes->assertStatus(200)
        ->assertJsonPath('status', 'success');

    // Verificar que ya no se encuentra
    $this->getJson("http://{$this->domain}/api-tenant/customer/{$customerId}")->assertStatus(404);
});

it('POST /api-tenant/customer/filter and GET /api-tenant/customer/metrics', function () {
    $this->postJson("http://{$this->domain}/api-tenant/customer/create", [
        'name' => 'Carla Diaz',
        'email' => 'carla@empresa.cl',
        'is_active' => true,
        'accepts_marketing' => true,
    ]);
    $this->postJson("http://{$this->domain}/api-tenant/customer/create", [
        'name' => 'Ignacio Soto',
        'email' => 'ignacio@empresa.cl',
        'is_active' => true,
        'accepts_marketing' => false,
    ]);

    // Filtro
    $filterRes = $this->postJson("http://{$this->domain}/api-tenant/customer/filter", [
        'search' => 'Carla',
    ]);
    $filterRes->assertStatus(200)
        ->assertJsonCount(1, 'data.data');

    // Métricas
    $metricsRes = $this->getJson("http://{$this->domain}/api-tenant/customer/metrics");
    $metricsRes->assertStatus(200)
        ->assertJsonPath('data.total_customers', 2)
        ->assertJsonPath('data.marketing_subscribers', 1);
});

it('manages customer addresses through API endpoints', function () {
    $create = $this->postJson("http://{$this->domain}/api-tenant/customer/create", [
        'name' => 'Rodrigo Bravo',
        'email' => 'rodrigo@empresa.cl',
    ]);
    $customerId = $create->json('data.id');

    // 1. Agregar dirección
    $addAddrRes = $this->postJson("http://{$this->domain}/api-tenant/customer/{$customerId}/address", [
        'first_name' => 'Rodrigo',
        'last_name' => 'Bravo',
        'address_line_1' => 'Calle Los Aromos 123',
        'city' => 'Santiago',
        'state' => 'RM',
        'postal_code' => '8320000',
        'country' => 'Chile',
        'type' => 'shipping',
        'is_default' => true,
    ]);
    $addAddrRes->assertStatus(201)
        ->assertJsonCount(1, 'data.addresses');

    $addressId = $addAddrRes->json('data.addresses.0.id');

    // 2. Establecer por defecto
    $defaultRes = $this->postJson("http://{$this->domain}/api-tenant/customer/{$customerId}/address/{$addressId}/default");
    $defaultRes->assertStatus(200)
        ->assertJsonPath('data.addresses.0.is_default', true);

    // 3. Eliminar dirección
    $delAddrRes = $this->deleteJson("http://{$this->domain}/api-tenant/customer/{$customerId}/address/{$addressId}");
    $delAddrRes->assertStatus(200)
        ->assertJsonCount(0, 'data.addresses');
});
