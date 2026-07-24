<?php

use Tests\TestCase;
use Src\Authentication\Domain\Shared\Security\UuidGenerator as AuthUuidGenerator;
use Src\Authentication\Domain\ValueObjects\Uuid as AuthUuid;
use Src\Authentication\Infrastructure\Security\LaravelUuidGenerator as AuthLaravelUuidGenerator;
use Src\User\Domain\Shared\Security\UuidGenerator as UserUuidGenerator;
use Src\User\Domain\ValueObjects\Uuid as UserUuid;
use Src\Tenant\Domain\Shared\Security\UuidGenerator as TenantUuidGenerator;
use Src\Tenant\Domain\ValuesObjects\Uuid as TenantUuid;
use Src\Product\Domain\Shared\Security\UuidGenerator as ProductUuidGenerator;
use Src\Product\Domain\ValueObjects\Uuid as ProductUuid;
use Src\Admin\Domain\Shared\Security\UuidGenerator as AdminUuidGenerator;
use Src\Admin\Domain\ValueObjects\Uuid as AdminUuid;

uses(TestCase::class);

test('Authentication Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new AuthLaravelUuidGenerator();
    $uuid = AuthUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(AuthUuid::class);
    expect(AuthUuid::isValid($uuid->value()))->toBeTrue();
});

test('Authentication Uuid detects invalid string formats without framework dependency', function () {
    expect(AuthUuid::isValid('invalid-uuid-string'))->toBeFalse();
    expect(AuthUuid::isValid('12345678-1234-1234-1234-123456789abc'))->toBeTrue();
});

test('User Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new \Src\User\Infrastructure\Security\LaravelUuidGenerator();
    $uuid = UserUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(UserUuid::class);
    expect(UserUuid::isValid($uuid->value()))->toBeTrue();
});

test('Tenant Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new \Src\Tenant\Infrastructure\Security\LaravelUuidGenerator();
    $uuid = TenantUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(TenantUuid::class);
    expect(TenantUuid::isValid($uuid->value()))->toBeTrue();
});

test('Product Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new \Src\Product\Infrastructure\Security\LaravelUuidGenerator();
    $uuid = ProductUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(ProductUuid::class);
    expect(ProductUuid::isValid($uuid->value()))->toBeTrue();
});

test('Admin Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new \Src\Admin\Infrastructure\Security\LaravelUuidGenerator();
    $uuid = AdminUuid::generate($generator);

    expect($uuid)->toBeInstanceOf(AdminUuid::class);
    expect(AdminUuid::isValid($uuid->value()))->toBeTrue();
});

test('Service Providers bind UuidGenerator interface to LaravelUuidGenerator', function () {
    expect(app(AuthUuidGenerator::class))->toBeInstanceOf(AuthLaravelUuidGenerator::class);
    expect(app(UserUuidGenerator::class))->toBeInstanceOf(\Src\User\Infrastructure\Security\LaravelUuidGenerator::class);
    expect(app(TenantUuidGenerator::class))->toBeInstanceOf(\Src\Tenant\Infrastructure\Security\LaravelUuidGenerator::class);
    expect(app(ProductUuidGenerator::class))->toBeInstanceOf(\Src\Product\Infrastructure\Security\LaravelUuidGenerator::class);
    expect(app(AdminUuidGenerator::class))->toBeInstanceOf(\Src\Admin\Infrastructure\Security\LaravelUuidGenerator::class);
});
