<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
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

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Payment API Store',
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
    $this->tenantUser = actingAsTenantOwner();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('GET /api-tenant/payment/gateways returns list of registered payment methods', function () {
    $response = $this->getJson("http://{$this->domain}/api-tenant/payment/gateways");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'data' => [
                '*' => ['identifier', 'display_name', 'is_offline'],
            ],
        ]);

    $identifiers = collect($response->json('data'))->pluck('identifier')->all();
    expect($identifiers)->toContain('manual_transfer')
        ->and($identifiers)->toContain('cash_on_delivery');
});

it('POST /api-tenant/payment/process processes manual transfer payment successfully', function () {
    $payload = [
        'amount' => 350.00,
        'currency' => 'USD',
        'customer_email' => 'pagador@test.com',
        'customer_name' => 'Pagador Test',
        'payment_method' => 'manual_transfer',
        'order_id' => 'ORD-999',
        'description' => 'Compra en tienda online',
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/payment/process", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.success', true)
        ->assertJsonPath('data.status', 'pending');

    expect($response->json('data.transaction_id'))->toStartWith('TX-BT-');
});

it('POST /api-tenant/payment/process returns 404 on unknown gateway', function () {
    $payload = [
        'amount' => 100.00,
        'currency' => 'USD',
        'customer_email' => 'test@test.com',
        'customer_name' => 'Test',
        'payment_method' => 'cryptocurrency_gateway',
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/payment/process", $payload);

    $response->assertStatus(404)
        ->assertJsonPath('status', 'error');
});

it('POST /api-tenant/payment/process returns 422 on validation failure', function () {
    $payload = [
        'amount' => -10, // Invalid amount
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/payment/process", $payload);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonStructure(['errors']);
});
