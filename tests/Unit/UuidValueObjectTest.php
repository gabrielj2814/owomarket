<?php

use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\Exceptions\InvalidUuidException;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Shared\Infrastructure\Security\LaravelPasswordHasher;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\Shared\Infrastructure\Security\StrictPasswordValidator;
use Tests\TestCase;

uses(TestCase::class);

test('Shared Kernel Uuid can be generated using injected LaravelUuidGenerator', function () {
    $generator = new LaravelUuidGenerator;
    $uuid = Uuid::generate($generator);

    expect($uuid)->toBeInstanceOf(Uuid::class);
    expect(Uuid::isValid($uuid->value()))->toBeTrue();
    expect($uuid->isV4())->toBeTrue();
});

test('Shared Kernel Uuid detects invalid string formats without framework dependency', function () {
    expect(Uuid::isValid('invalid-uuid-string'))->toBeFalse();
    expect(Uuid::isValid('12345678-1234-1234-1234-123456789abc'))->toBeTrue();
    expect(fn () => Uuid::make('invalid-uuid-string'))->toThrow(InvalidUuidException::class);
});

test('Shared Kernel Uuid supports make, equals and __toString', function () {
    $raw = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
    $uuid1 = Uuid::make($raw);
    $uuid2 = Uuid::make($raw);

    expect($uuid1->equals($uuid2))->toBeTrue();
    expect((string) $uuid1)->toBe($raw);
    expect($uuid1->value())->toBe($raw);
});

test('SharedServiceProvider binds Shared Kernel interfaces to infrastructure implementations', function () {
    expect(app(UuidGenerator::class))->toBeInstanceOf(LaravelUuidGenerator::class);
    expect(app(PasswordHasher::class))->toBeInstanceOf(LaravelPasswordHasher::class);
    expect(app(PasswordValidator::class))->toBeInstanceOf(StrictPasswordValidator::class);
});
