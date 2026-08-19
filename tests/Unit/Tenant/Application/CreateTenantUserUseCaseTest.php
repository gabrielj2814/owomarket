<?php

declare(strict_types=1);

use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Application\UseCase\CreateTenantUserUseCase;
use Src\Tenant\Domain\Entities\TenantUser;
use Src\Tenant\Domain\ValueObjects\RoleTenantUser;

/**
 * Regresión: TenantUser::create() exige UuidGenerator como primer parámetro.
 * Antes de la corrección se pasaba $uuid_tenant en esa posición, lo que además
 * desplazaba todos los argumentos (tenantId recibía el userId, etc.).
 */
const TENANT_USER_ID = '11111111-2222-4333-8444-555555555555';
const TENANT_USER_TENANT_ID = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';
const TENANT_USER_OWNER_ID = '99999999-8888-4777-8666-555544443333';

function makeCreateTenantUserUseCase(TenantUserRepositoryInterface $repository): CreateTenantUserUseCase
{
    $generator = Mockery::mock(UuidGenerator::class);
    $generator->shouldReceive('generate')->andReturn(TENANT_USER_ID);

    return new CreateTenantUserUseCase($repository, $generator);
}

test('CreateTenantUserUseCase asigna tenantId y userId en el orden correcto', function () {
    $repository = Mockery::mock(TenantUserRepositoryInterface::class);
    $repository->shouldReceive('assignTenantToUser')
        ->once()
        ->with(Mockery::type(TenantUser::class))
        ->andReturnUsing(fn (TenantUser $tenantUser) => $tenantUser);

    $useCase = makeCreateTenantUserUseCase($repository);

    $tenantUser = $useCase->execute(TENANT_USER_OWNER_ID, TENANT_USER_TENANT_ID);

    // El bug desplazaba los argumentos: el id propio quedaba con el uuid del tenant.
    expect($tenantUser->getId()->value())->toBe(TENANT_USER_ID);
    expect($tenantUser->getTenantId()->value())->toBe(TENANT_USER_TENANT_ID);
    expect($tenantUser->getUserId()->value())->toBe(TENANT_USER_OWNER_ID);
    expect($tenantUser->getRole()->value())->toBe(RoleTenantUser::ROLE_OWNER);
    expect($tenantUser->getCreatedAt())->not->toBeNull();
});

test('CreateTenantUserUseCase no lanza TypeError por falta del UuidGenerator', function () {
    $repository = Mockery::mock(TenantUserRepositoryInterface::class);
    $repository->shouldReceive('assignTenantToUser')->andReturnUsing(fn (TenantUser $tenantUser) => $tenantUser);

    $useCase = makeCreateTenantUserUseCase($repository);

    expect(fn () => $useCase->execute(TENANT_USER_OWNER_ID, TENANT_USER_TENANT_ID))
        ->not->toThrow(TypeError::class);
});
