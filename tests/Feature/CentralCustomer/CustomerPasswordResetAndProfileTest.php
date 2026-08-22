<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerAddress;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerPasswordReset;
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

    if (! Schema::hasTable('central_customers')) {
        (require base_path('database/migrations/2026_08_19_000001_create_central_customers_tables.php'))->up();
    }
    if (! Schema::hasTable('central_customer_password_resets')) {
        (require base_path('database/migrations/2026_08_19_000009_create_central_customer_password_resets_table.php'))->up();
    }
});

test('POST /api/central/customer/forgot-password sends recovery PIN', function () {
    $email = 'forgot_'.bin2hex(random_bytes(3)).'@example.com';
    CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Forgot User',
        'email' => $email,
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/central/customer/forgot-password', [
        'email' => $email,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'code' => 200,
            'status' => 'success',
            'data' => [
                'email' => $email,
            ],
        ]);

    expect(CentralCustomerPasswordReset::where('email', $email)->exists())->toBeTrue();
});

test('POST /api/central/customer/reset-password resets password using PIN', function () {
    $email = 'reset_'.bin2hex(random_bytes(3)).'@example.com';
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Reset User',
        'email' => $email,
        'password' => Hash::make('old_pass_123'),
    ]);

    CentralCustomerPasswordReset::create([
        'id' => (string) Str::uuid(),
        'email' => $email,
        'pin_code' => '889900',
        'token' => (string) Str::random(64),
        'expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->postJson('/api/central/customer/reset-password', [
        'email' => $email,
        'pin_code' => '889900',
        'password' => 'new_secure_pass_999',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'code' => 200,
            'status' => 'success',
        ]);

    $customer->refresh();
    expect(Hash::check('new_secure_pass_999', $customer->password))->toBeTrue();
});

test('PUT /api/central/customer/profile/{id} updates customer profile and addresses', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Elena Silva',
        'email' => 'elena_'.bin2hex(random_bytes(3)).'@example.com',
        'phone' => '0412-5555555',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->actingAs($customer, 'central_customer')->putJson("/api/central/customer/profile/{$customer->id}", [
        'name' => 'Elena Silva De Martínez',
        'phone' => '0424-7777777',
        'document_id' => 'V-18765432',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'code' => 200,
            'status' => 'success',
            'data' => [
                'customer' => [
                    'name' => 'Elena Silva De Martínez',
                    'phone' => '0424-7777777',
                    'document_id' => 'V-18765432',
                ],
            ],
        ]);
});

test('Address endpoints (update, set default, delete) work correctly', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Address User',
        'email' => 'addr_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => Hash::make('password123'),
    ]);

    $addr1 = CentralCustomerAddress::create([
        'id' => (string) Str::uuid(),
        'customer_id' => $customer->id,
        'label' => 'Casa',
        'address' => 'Av. Francisco de Miranda',
        'city' => 'Caracas',
        'country' => 'VE',
        'is_default' => true,
    ]);

    $addr2 = CentralCustomerAddress::create([
        'id' => (string) Str::uuid(),
        'customer_id' => $customer->id,
        'label' => 'Oficina',
        'address' => 'Torre Polar, Plaza Venezuela',
        'city' => 'Caracas',
        'country' => 'VE',
        'is_default' => false,
    ]);

    // 1. Update address 1
    $updateRes = $this->actingAs($customer, 'central_customer')->putJson("/api/central/customer/profile/{$customer->id}/address/{$addr1->id}", [
        'label' => 'Casa Principal',
        'city' => 'Chacao, Caracas',
    ]);
    $updateRes->assertStatus(200)
        ->assertJsonPath('data.address.label', 'Casa Principal')
        ->assertJsonPath('data.address.city', 'Chacao, Caracas');

    // 2. Set address 2 as default
    $defaultRes = $this->actingAs($customer, 'central_customer')->patchJson("/api/central/customer/profile/{$customer->id}/address/{$addr2->id}/default");
    $defaultRes->assertStatus(200)
        ->assertJsonPath('data.address.is_default', true);

    $addr1->refresh();
    expect($addr1->is_default)->toBeFalse();

    // 3. Delete address 1
    $deleteRes = $this->actingAs($customer, 'central_customer')->deleteJson("/api/central/customer/profile/{$customer->id}/address/{$addr1->id}");
    $deleteRes->assertStatus(200);
    expect(CentralCustomerAddress::where('id', $addr1->id)->exists())->toBeFalse();

    // 4. Otro cliente no puede tocar las direcciones de este perfil.
    $stranger = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Stranger',
        'email' => 'stranger_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => Hash::make('password123'),
    ]);
    $this->actingAs($stranger, 'central_customer')
        ->patchJson("/api/central/customer/profile/{$customer->id}/address/{$addr2->id}/default")
        ->assertStatus(403);
});
