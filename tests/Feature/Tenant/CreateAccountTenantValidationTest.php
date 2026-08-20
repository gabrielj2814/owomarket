<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Domain;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
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
});

test('POST /tenant/create/account fails validation when required fields are missing', function () {
    $response = $this->postJson('/tenant/create/account', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'status',
            'code',
            'message',
            'errors' => [
                'name',
                'email',
                'phone',
                'password',
                'store_name',
                'tenant_name',
            ],
        ]);
});

test('POST /tenant/create/account fails validation with duplicate email', function () {
    $email = 'existing_tenant_owner_'.bin2hex(random_bytes(3)).'@owomarket.com';

    User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Owner Existente',
        'email' => $email,
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
        'phone' => '04121234567',
        'avatar' => 'https://via.placeholder.com/150',
        'is_active' => true,
    ]);

    $payload = [
        'name' => 'Comercio Duplicado',
        'email' => $email,
        'phone' => '04121234567',
        'password' => 'SecurePass123!',
        'confirmPassword' => 'SecurePass123!',
        'store_name' => 'Comercio Nuevo Unico ' . bin2hex(random_bytes(3)),
        'tenant_name' => 'comercio-nuevo-' . bin2hex(random_bytes(3)) . '.owomarket.local',
    ];

    $response = $this->postJson('/tenant/create/account', $payload);

    $response->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'Este correo electrónico ya se encuentra registrado.');
});

test('POST /tenant/create/account fails validation with mismatching password confirmation', function () {
    $payload = [
        'name' => 'Comercio Pass Test',
        'email' => 'unique_' . bin2hex(random_bytes(3)) . '@example.com',
        'phone' => '04121234567',
        'password' => 'SecurePass123!',
        'confirmPassword' => 'DifferentPass456!',
        'store_name' => 'Comercio Pass ' . bin2hex(random_bytes(3)),
        'tenant_name' => 'comercio-pass-' . bin2hex(random_bytes(3)) . '.owomarket.local',
    ];

    $response = $this->postJson('/tenant/create/account', $payload);

    $response->assertStatus(422)
        ->assertJsonPath('errors.confirmPassword.0', 'Las contraseñas no coinciden.');
});
