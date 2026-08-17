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

    $migrationZone = require base_path('database/migrations/tenant/2025_10_28_145209_create_shipping_zones.php');
    if (! Schema::hasTable('shipping_zones')) {
        $migrationZone->up();
    }

    $migrationRate = require base_path('database/migrations/tenant/2025_10_28_145238_create_shipping_rates.php');
    if (! Schema::hasTable('shipping_rates')) {
        $migrationRate->up();
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

test('POST /api-tenant/shipping/zones/create creates shipping zone and returns 201', function () {
    $payload = [
        'name' => 'Zona México',
        'countries' => ['MX'],
        'states' => ['CDMX', 'JAL', 'NL'],
        'priority' => 1,
        'is_active' => true,
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/shipping/zones/create", $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'code' => 201,
            'message' => 'Zona de envío creada exitosamente',
            'data' => [
                'name' => 'Zona México',
                'countries' => ['MX'],
            ],
        ]);
});

test('POST /api-tenant/shipping/zones/{id}/rates creates a shipping rate', function () {
    $zoneResp = $this->postJson("http://{$this->domain}/api-tenant/shipping/zones/create", [
        'name' => 'Zona Local',
    ]);
    $zoneId = $zoneResp->json('data.id');

    $ratePayload = [
        'name' => 'Envío Exprés',
        'type' => 'flat',
        'cost' => 12.50,
        'is_active' => true,
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/shipping/zones/{$zoneId}/rates", $ratePayload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'code' => 201,
            'message' => 'Tarifa de envío creada exitosamente',
            'data' => [
                'name' => 'Envío Exprés',
                'cost' => 12.50,
            ],
        ]);
});

test('POST /api-tenant/shipping/calculate calculates shipping options', function () {
    $zoneResp = $this->postJson("http://{$this->domain}/api-tenant/shipping/zones/create", [
        'name' => 'Nacional',
        'countries' => ['MX'],
    ]);
    $zoneId = $zoneResp->json('data.id');

    $this->postJson("http://{$this->domain}/api-tenant/shipping/zones/{$zoneId}/rates", [
        'name' => 'Estándar',
        'type' => 'flat',
        'cost' => 10.0,
    ]);

    $this->postJson("http://{$this->domain}/api-tenant/shipping/zones/{$zoneId}/rates", [
        'name' => 'Gratis',
        'type' => 'free',
        'cost' => 0.0,
        'min_value' => 50.0,
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/shipping/calculate", [
        'order_value' => 80.0,
        'country' => 'MX',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'code' => 200,
        ]);

    expect(count($response->json('data.options')))->toBe(2)
        ->and($response->json('data.recommended_option.cost'))->toBe(0);
});
