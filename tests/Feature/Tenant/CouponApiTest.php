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

    $migration = require base_path('database/migrations/tenant/2025_10_28_144655_create_coupons.php');
    if (! Schema::hasTable('coupons')) {
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

test('POST /api-tenant/coupon/create creates a new coupon and returns 201', function () {
    $payload = [
        'code' => 'blackfriday',
        'type' => 'percentage',
        'value' => 25.0,
        'valid_from' => '2026-11-01',
        'valid_to' => '2026-11-30',
        'min_order_amount' => 50.0,
        'usage_limit' => 100,
        'usage_limit_per_customer' => 1,
        'is_active' => true,
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/coupon/create", $payload);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'code' => 201,
            'message' => 'Cupón creado exitosamente',
            'data' => [
                'code' => 'BLACKFRIDAY',
                'type' => 'percentage',
                'value' => 25.0,
            ],
        ]);
});

test('POST /api-tenant/coupon/create returns 422 on validation failure', function () {
    $payload = [
        'code' => '',
        'type' => 'invalid_type',
        'value' => -5,
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/coupon/create", $payload);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'code' => 422,
        ]);
});

test('POST /api-tenant/coupon/filter returns paginated coupons', function () {
    $this->postJson("http://{$this->domain}/api-tenant/coupon/create", [
        'code' => 'FILTERME',
        'type' => 'fixed_amount',
        'value' => 10.0,
        'valid_from' => '2026-01-01',
        'valid_to' => '2026-12-31',
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/coupon/filter", [
        'search' => 'FILTERME',
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

test('GET /api-tenant/coupon/{id} returns existing coupon', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/coupon/create", [
        'code' => 'SINGLEGET',
        'type' => 'percentage',
        'value' => 15.0,
        'valid_from' => '2026-01-01',
        'valid_to' => '2026-12-31',
    ]);

    $id = $created->json('data.id');

    $response = $this->getJson("http://{$this->domain}/api-tenant/coupon/{$id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $id,
                'code' => 'SINGLEGET',
            ],
        ]);
});

test('PUT /api-tenant/coupon/{id} updates existing coupon', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/coupon/create", [
        'code' => 'TOUPDATE',
        'type' => 'percentage',
        'value' => 10.0,
        'valid_from' => '2026-01-01',
        'valid_to' => '2026-12-31',
    ]);

    $id = $created->json('data.id');

    $response = $this->putJson("http://{$this->domain}/api-tenant/coupon/{$id}", [
        'code' => 'UPDATEDCODE',
        'type' => 'fixed_amount',
        'value' => 20.0,
        'valid_from' => '2026-02-01',
        'valid_to' => '2026-10-31',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $id,
                'code' => 'UPDATEDCODE',
                'type' => 'fixed_amount',
            ],
        ]);
});

test('DELETE /api-tenant/coupon/{id} deletes coupon', function () {
    $created = $this->postJson("http://{$this->domain}/api-tenant/coupon/create", [
        'code' => 'TODELETE',
        'type' => 'percentage',
        'value' => 5.0,
        'valid_from' => '2026-01-01',
        'valid_to' => '2026-12-31',
    ]);

    $id = $created->json('data.id');

    $response = $this->deleteJson("http://{$this->domain}/api-tenant/coupon/{$id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Cupón eliminado exitosamente',
        ]);
});

test('POST /api-tenant/coupon/validate validates coupon and calculates discount', function () {
    $this->postJson("http://{$this->domain}/api-tenant/coupon/create", [
        'code' => 'PROMO50',
        'type' => 'percentage',
        'value' => 50.0,
        'valid_from' => '2026-01-01',
        'valid_to' => '2026-12-31',
        'min_order_amount' => 100.0,
    ]);

    $validResp = $this->postJson("http://{$this->domain}/api-tenant/coupon/validate", [
        'code' => 'promo50',
        'order_subtotal' => 200.0,
    ]);

    $validResp->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'is_valid' => true,
                'discount_amount' => 100.0,
                'final_total' => 100.0,
            ],
        ]);

    $invalidResp = $this->postJson("http://{$this->domain}/api-tenant/coupon/validate", [
        'code' => 'promo50',
        'order_subtotal' => 50.0,
    ]);

    $invalidResp->assertStatus(400)
        ->assertJson([
            'status' => 'error',
            'code' => 400,
        ]);
});
