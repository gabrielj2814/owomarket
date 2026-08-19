<?php

declare(strict_types=1);

use App\Models\CentralCustomer;
use App\Models\CentralCustomerPasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\SendCentralCustomerPasswordResetPinUseCase;
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

test('SendCentralCustomerPasswordResetPinUseCase generates 6-digit PIN and token for valid email', function () {
    $email = 'pedro_'.bin2hex(random_bytes(3)).'@example.com';
    CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Pedro Perez',
        'email' => $email,
        'password' => 'secret123',
    ]);

    $useCase = new SendCentralCustomerPasswordResetPinUseCase;
    $result = $useCase->execute($email);

    expect($result['success'])->toBeTrue();
    expect($result['email'])->toBe($email);
    expect($result['pin_code'])->toHaveLength(6);

    $record = CentralCustomerPasswordReset::where('email', $email)->first();
    expect($record)->not->toBeNull();
    expect($record->pin_code)->toBe($result['pin_code']);
    expect($record->expires_at)->toBeGreaterThan(now());
});

test('SendCentralCustomerPasswordResetPinUseCase throws 404 for non-existent email', function () {
    $useCase = new SendCentralCustomerPasswordResetPinUseCase;
    $useCase->execute('nonexistent@example.com');
})->throws(Exception::class, 'No existe una cuenta registrada con este correo electrónico.');
