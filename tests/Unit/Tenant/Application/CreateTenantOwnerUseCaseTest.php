<?php

declare(strict_types=1);

use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\UserType;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Application\UseCase\CreateTenantOwnerUseCase;
use Src\Tenant\Domain\Entities\TenantOwner;

/**
 * Regresión: TenantOwner::create() exige UuidGenerator como primer parámetro y
 * solo acepta 10 argumentos (createdAt/updatedAt los genera internamente).
 * Antes de la corrección se invocaba sin generador y con 11 argumentos.
 */
const TENANT_OWNER_UUID = '3c8f1b52-7d64-4e91-a2f8-5b6c7d8e9f01';

function makeCreateTenantOwnerUseCase(TenantOwnerRepositoryInterface $repository): CreateTenantOwnerUseCase
{
    $validator = Mockery::mock(PasswordValidator::class);
    $validator->shouldReceive('validate')->andReturnNull();

    $hasher = Mockery::mock(PasswordHasher::class);
    $hasher->shouldReceive('hash')->andReturn('$2y$10$ownerhashownerhashown');

    $generator = Mockery::mock(UuidGenerator::class);
    $generator->shouldReceive('generate')->andReturn(TENANT_OWNER_UUID);

    return new CreateTenantOwnerUseCase($repository, $validator, $hasher, $generator);
}

test('CreateTenantOwnerUseCase crea el owner con UUID inyectado, tipo tenant_owner y timestamps de la entidad', function () {
    $repository = Mockery::mock(TenantOwnerRepositoryInterface::class);
    $repository->shouldReceive('createTenantOwner')
        ->once()
        ->with(Mockery::type(TenantOwner::class))
        ->andReturnUsing(fn (TenantOwner $owner) => $owner);

    $useCase = makeCreateTenantOwnerUseCase($repository);

    $owner = $useCase->execute('Luisa Serra', 'owner@owomarket.test', '04141234567', 'Password123!');

    expect($owner)->toBeInstanceOf(TenantOwner::class);
    expect($owner->getId()->value())->toBe(TENANT_OWNER_UUID);
    expect($owner->getEmail()->value())->toBe('owner@owomarket.test');
    expect($owner->getType()->value())->toBe(UserType::TENANT_OWNER);
    expect($owner->getCreatedAt())->not->toBeNull();
    expect($owner->getUpdatedAt())->not->toBeNull();
});

test('CreateTenantOwnerUseCase no lanza TypeError por falta del UuidGenerator', function () {
    $repository = Mockery::mock(TenantOwnerRepositoryInterface::class);
    $repository->shouldReceive('createTenantOwner')->andReturnUsing(fn (TenantOwner $owner) => $owner);

    $useCase = makeCreateTenantOwnerUseCase($repository);

    expect(fn () => $useCase->execute('Ana Perez', 'ana@owomarket.test', '04141234567', 'Password123!'))
        ->not->toThrow(TypeError::class);
});
