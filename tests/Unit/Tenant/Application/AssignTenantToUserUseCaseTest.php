<?php

declare(strict_types=1);

use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Application\UseCase\AssignTenantToUserUseCase;
use Src\Tenant\Domain\Entities\TenantUser;
use Src\Tenant\Domain\ValueObjects\RoleTenantUser;

/**
 * Regresión: TenantUser::create() se invoca aquí con argumentos nombrados y
 * omitía el parámetro obligatorio `generator:`, provocando ArgumentCountError.
 */
const ASSIGN_TENANT_USER_ID = 'abcdabcd-1234-4567-89ab-cdefcdefcdef';
const ASSIGN_TENANT_ID = 'dddddddd-cccc-4bbb-8aaa-999999999999';
const ASSIGN_USER_ID = '12341234-5678-49ab-8cde-f01234567890';

function makeAssignTenantToUserUseCase(TenantUserRepositoryInterface $repository): AssignTenantToUserUseCase
{
    $generator = Mockery::mock(UuidGenerator::class);
    $generator->shouldReceive('generate')->andReturn(ASSIGN_TENANT_USER_ID);

    return new AssignTenantToUserUseCase($repository, $generator);
}

test('AssignTenantToUserUseCase construye la relación con el generador y el rol indicado', function () {
    $repository = Mockery::mock(TenantUserRepositoryInterface::class);
    $repository->shouldReceive('assignTenantToUser')
        ->once()
        ->with(Mockery::type(TenantUser::class))
        ->andReturnUsing(fn (TenantUser $tenantUser) => $tenantUser);

    $useCase = makeAssignTenantToUserUseCase($repository);

    $tenantUser = $useCase->execute(ASSIGN_TENANT_ID, ASSIGN_USER_ID, RoleTenantUser::ROLE_ADMIN);

    expect($tenantUser->getId()->value())->toBe(ASSIGN_TENANT_USER_ID);
    expect($tenantUser->getTenantId()->value())->toBe(ASSIGN_TENANT_ID);
    expect($tenantUser->getUserId()->value())->toBe(ASSIGN_USER_ID);
    expect($tenantUser->getRole()->value())->toBe(RoleTenantUser::ROLE_ADMIN);
});

test('AssignTenantToUserUseCase no lanza ArgumentCountError por el argumento nombrado generator', function () {
    $repository = Mockery::mock(TenantUserRepositoryInterface::class);
    $repository->shouldReceive('assignTenantToUser')->andReturnUsing(fn (TenantUser $tenantUser) => $tenantUser);

    $useCase = makeAssignTenantToUserUseCase($repository);

    expect(fn () => $useCase->execute(ASSIGN_TENANT_ID, ASSIGN_USER_ID, RoleTenantUser::ROLE_OWNER))
        ->not->toThrow(ArgumentCountError::class);
});

test('AssignTenantToUserUseCase propaga los permisos recibidos', function () {
    $repository = Mockery::mock(TenantUserRepositoryInterface::class);
    $repository->shouldReceive('assignTenantToUser')->andReturnUsing(fn (TenantUser $tenantUser) => $tenantUser);

    $useCase = makeAssignTenantToUserUseCase($repository);

    $tenantUser = $useCase->execute(
        ASSIGN_TENANT_ID,
        ASSIGN_USER_ID,
        RoleTenantUser::ROLE_STAFF,
        ['products.view', 'orders.view']
    );

    expect($tenantUser->getPermissions())->toBe(['products.view', 'orders.view']);
});
