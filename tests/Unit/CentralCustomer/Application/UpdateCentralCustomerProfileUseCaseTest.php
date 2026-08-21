<?php

declare(strict_types=1);

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\UpdateCentralCustomerProfileUseCase;
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
});

test('UpdateCentralCustomerProfileUseCase updates customer basic info', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Original Name',
        'email' => 'update_'.bin2hex(random_bytes(3)).'@example.com',
        'phone' => '0412-1111111',
        'document_id' => 'V-12345678',
        'password' => Hash::make('password123'),
    ]);

    $useCase = new UpdateCentralCustomerProfileUseCase;
    $updated = $useCase->execute($customer->id, [
        'name' => 'Updated Name',
        'phone' => '0414-9999999',
        'document_id' => 'J-99887766-0',
    ]);

    expect($updated->name)->toBe('Updated Name');
    expect($updated->phone)->toBe('0414-9999999');
    expect($updated->document_id)->toBe('J-99887766-0');
});

test('UpdateCentralCustomerProfileUseCase updates password with current password verification', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Password User',
        'email' => 'pw_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => Hash::make('current_pass_123'),
    ]);

    $useCase = new UpdateCentralCustomerProfileUseCase;
    $updated = $useCase->execute($customer->id, [
        'current_password' => 'current_pass_123',
        'new_password' => 'new_pass_456789',
    ]);

    expect(Hash::check('new_pass_456789', $updated->password))->toBeTrue();
});

test('UpdateCentralCustomerProfileUseCase rejects wrong current password', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Wrong Password User',
        'email' => 'wrong_pw_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => Hash::make('correct_pass_123'),
    ]);

    $useCase = new UpdateCentralCustomerProfileUseCase;
    $useCase->execute($customer->id, [
        'current_password' => 'wrong_guess',
        'new_password' => 'new_pass_456789',
    ]);
})->throws(Exception::class, 'La contraseña actual ingresada es incorrecta.');
