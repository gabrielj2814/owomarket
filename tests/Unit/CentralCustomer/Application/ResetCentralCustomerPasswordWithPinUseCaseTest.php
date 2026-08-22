<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\ResetCentralCustomerPasswordWithPinUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerPasswordReset;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

uses(Tests\TestCase::class);

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

test('ResetCentralCustomerPasswordWithPinUseCase resets password with valid PIN and cleans token', function () {
    $email = 'maria_'.bin2hex(random_bytes(3)).'@example.com';
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Maria Gonzalez',
        'email' => $email,
        'password' => Hash::make('old_password_123'),
    ]);

    $pin = '654321';
    CentralCustomerPasswordReset::create([
        'id' => (string) Str::uuid(),
        'email' => $email,
        'pin_code' => $pin,
        'token' => (string) Str::random(64),
        'expires_at' => now()->addMinutes(10),
    ]);

    $useCase = new ResetCentralCustomerPasswordWithPinUseCase;
    $result = $useCase->execute($email, $pin, 'new_super_secret_999');

    expect($result['success'])->toBeTrue();

    $customer->refresh();
    expect(Hash::check('new_super_secret_999', $customer->password))->toBeTrue();

    // Verify token was deleted
    expect(CentralCustomerPasswordReset::where('email', $email)->exists())->toBeFalse();
});

test('ResetCentralCustomerPasswordWithPinUseCase rejects invalid or expired PIN', function () {
    $email = 'expired_'.bin2hex(random_bytes(3)).'@example.com';
    CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Expired User',
        'email' => $email,
        'password' => Hash::make('password123'),
    ]);

    CentralCustomerPasswordReset::create([
        'id' => (string) Str::uuid(),
        'email' => $email,
        'pin_code' => '111222',
        'token' => (string) Str::random(64),
        'expires_at' => now()->subMinutes(5), // Expired
    ]);

    $useCase = new ResetCentralCustomerPasswordWithPinUseCase;
    $useCase->execute($email, '111222', 'new_password_abc');
})->throws(Exception::class, 'El código de seguridad es inválido o ha expirado.');
