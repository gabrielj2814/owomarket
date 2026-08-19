<?php

use Src\Admin\Domain\ValueObjects\Uuid as AdminUuid;
use Src\Authentication\Domain\ValueObjects\Uuid as AuthUuid;
use Src\Product\Domain\ValueObjects\Uuid as ProductUuid;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\Uuid as SharedUuid;
use Src\Shared\Infrastructure\Security\LaravelPasswordHasher;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\Shared\Infrastructure\Security\StrictPasswordValidator;
use Src\Tenant\Domain\ValueObjects\Uuid as TenantUuid;
use Src\User\Domain\ValueObjects\Uuid as UserUuid;
use Tests\TestCase;

uses(TestCase::class);

test('Shared Kernel Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new LaravelUuidGenerator;
    $uuid = SharedUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(SharedUuid::class);
    expect(SharedUuid::isValid($uuid->value()))->toBeTrue();
});

test('Shared Kernel Uuid detects invalid string formats without framework dependency', function () {
    expect(SharedUuid::isValid('invalid-uuid-string'))->toBeFalse();
    expect(SharedUuid::isValid('12345678-1234-1234-1234-123456789abc'))->toBeTrue();
});

test('Authentication Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new LaravelUuidGenerator;
    $uuid = AuthUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(AuthUuid::class);
    expect(AuthUuid::isValid($uuid->value()))->toBeTrue();
});

test('User Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new LaravelUuidGenerator;
    $uuid = UserUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(UserUuid::class);
    expect(UserUuid::isValid($uuid->value()))->toBeTrue();
});

test('Tenant Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new LaravelUuidGenerator;
    $uuid = TenantUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(TenantUuid::class);
    expect(TenantUuid::isValid($uuid->value()))->toBeTrue();
});

test('Product Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new LaravelUuidGenerator;
    $uuid = ProductUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(ProductUuid::class);
    expect(ProductUuid::isValid($uuid->value()))->toBeTrue();
});

test('Admin Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new LaravelUuidGenerator;
    $uuid = AdminUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(AdminUuid::class);
    expect(AdminUuid::isValid($uuid->value()))->toBeTrue();
});

test('AppServiceProvider binds Shared Kernel interfaces to infrastructure implementations', function () {
    expect(app(UuidGenerator::class))->toBeInstanceOf(LaravelUuidGenerator::class);
    expect(app(PasswordHasher::class))->toBeInstanceOf(LaravelPasswordHasher::class);
    expect(app(PasswordValidator::class))->toBeInstanceOf(StrictPasswordValidator::class);
});
