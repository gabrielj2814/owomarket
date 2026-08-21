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
        'name' => 'Customer E2E Store',
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

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('executes full customer lifecycle end-to-end', function () {
    // 1. Consultar métricas iniciales (0 clientes)
    $initialMetrics = $this->getJson("http://{$this->domain}/api-tenant/customer/metrics");
    $initialMetrics->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.total_customers', 0);

    // 2. Registrar nuevo cliente con dirección inicial
    $createResponse = $this->postJson("http://{$this->domain}/api-tenant/customer/create", [
        'name' => 'Valentina Silva',
        'email' => 'valentina.silva@empresa.cl',
        'phone' => '+56977665544',
        'birth_date' => '1994-09-12',
        'gender' => 'female',
        'is_active' => true,
        'accepts_marketing' => true,
        'addresses' => [
            [
                'first_name' => 'Valentina',
                'last_name' => 'Silva',
                'address_line_1' => 'Av. Kennedy 5413',
                'address_line_2' => 'Depto 801',
                'city' => 'Las Condes',
                'state' => 'Región Metropolitana',
                'postal_code' => '7550000',
                'country' => 'Chile',
                'type' => 'shipping',
                'is_default' => true,
            ],
        ],
    ]);

    $createResponse->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Valentina Silva')
        ->assertJsonPath('data.email', 'valentina.silva@empresa.cl')
        ->assertJsonCount(1, 'data.addresses');

    $customerId = $createResponse->json('data.id');
    $shippingAddressId = $createResponse->json('data.addresses.0.id');

    // 3. Consultar métricas actualizadas (1 cliente total, 1 activo, 1 marketing)
    $metricsAfterCreate = $this->getJson("http://{$this->domain}/api-tenant/customer/metrics");
    $metricsAfterCreate->assertStatus(200)
        ->assertJsonPath('data.total_customers', 1)
        ->assertJsonPath('data.active_customers', 1)
        ->assertJsonPath('data.marketing_subscribers', 1);

    // 4. Consultar ficha de cliente por ID
    $showResponse = $this->getJson("http://{$this->domain}/api-tenant/customer/{$customerId}");
    $showResponse->assertStatus(200)
        ->assertJsonPath('data.id', $customerId)
        ->assertJsonPath('data.name', 'Valentina Silva')
        ->assertJsonPath('data.birth_date', '1994-09-12')
        ->assertJsonPath('data.addresses.0.is_default', true);

    // 5. Agregar una segunda dirección (Facturación)
    $addBillingAddressResponse = $this->postJson("http://{$this->domain}/api-tenant/customer/{$customerId}/address", [
        'first_name' => 'Valentina',
        'last_name' => 'Silva SpA',
        'company' => 'Silva Consulting SpA',
        'address_line_1' => 'Moneda 970',
        'address_line_2' => 'Oficina 503',
        'city' => 'Santiago Centro',
        'state' => 'Región Metropolitana',
        'postal_code' => '8320000',
        'country' => 'Chile',
        'type' => 'billing',
        'is_default' => false,
    ]);

    $addBillingAddressResponse->assertStatus(201)
        ->assertJsonCount(2, 'data.addresses');

    $billingAddressId = $addBillingAddressResponse->json('data.addresses.1.id');

    // 6. Establecer la segunda dirección como predeterminada
    $setDefaultResponse = $this->postJson("http://{$this->domain}/api-tenant/customer/{$customerId}/address/{$billingAddressId}/default");
    $setDefaultResponse->assertStatus(200);

    // 7. Filtrar clientes buscando por nombre
    $filterResponse = $this->postJson("http://{$this->domain}/api-tenant/customer/filter", [
        'search' => 'Valentina',
        'is_active' => true,
    ]);
    $filterResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.data');

    // 8. Actualizar datos de perfil del cliente
    $updateResponse = $this->putJson("http://{$this->domain}/api-tenant/customer/{$customerId}", [
        'name' => 'Valentina Silva R.',
        'email' => 'v.silva@empresa.cl',
        'phone' => '+56988889999',
        'birth_date' => '1994-09-12',
        'gender' => 'female',
        'is_active' => true,
        'accepts_marketing' => false,
    ]);

    $updateResponse->assertStatus(200)
        ->assertJsonPath('data.name', 'Valentina Silva R.')
        ->assertJsonPath('data.email', 'v.silva@empresa.cl')
        ->assertJsonPath('data.accepts_marketing', false);

    // 9. Eliminar la primera dirección
    $deleteAddressResponse = $this->deleteJson("http://{$this->domain}/api-tenant/customer/{$customerId}/address/{$shippingAddressId}");
    $deleteAddressResponse->assertStatus(200)
        ->assertJsonCount(1, 'data.addresses');

    // 10. Eliminar el cliente (Soft Delete)
    $deleteCustomerResponse = $this->deleteJson("http://{$this->domain}/api-tenant/customer/{$customerId}");
    $deleteCustomerResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');

    // 11. Verificar que el cliente ya no aparece en el filtro
    $filterAfterDelete = $this->postJson("http://{$this->domain}/api-tenant/customer/filter", [
        'search' => 'Valentina',
    ]);
    $filterAfterDelete->assertStatus(200)
        ->assertJsonCount(0, 'data.data');

    // 12. Verificar que las métricas reflejan 0 clientes activos
    $finalMetrics = $this->getJson("http://{$this->domain}/api-tenant/customer/metrics");
    $finalMetrics->assertStatus(200)
        ->assertJsonPath('data.total_customers', 0)
        ->assertJsonPath('data.active_customers', 0);
});
