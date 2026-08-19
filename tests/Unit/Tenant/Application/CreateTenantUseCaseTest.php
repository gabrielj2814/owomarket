<?php

declare(strict_types=1);

use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Application\UseCase\CreateTenantUseCase;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\ValueObjects\Slug;
use Src\Tenant\Domain\ValueObjects\TenantName;

/**
 * Regresión: Tenant::create() exige UuidGenerator como primer parámetro.
 * Antes de la corrección, CreateTenantUseCase invocaba Tenant::create($name, ...)
 * y lanzaba TypeError: Argument #1 ($generator) must be of type UuidGenerator.
 */
const TENANT_UUID = '9b2d1f6e-4c3a-4b8f-9d7e-1a2b3c4d5e6f';

function tenantUuidGeneratorStub(): UuidGenerator
{
    $generator = Mockery::mock(UuidGenerator::class);
    $generator->shouldReceive('generate')->andReturn(TENANT_UUID);

    return $generator;
}

test('CreateTenantUseCase crea el tenant con el UUID del generador inyectado', function () {
    $repository = Mockery::mock(TenantRepositoryInterface::class);
    $repository->shouldReceive('consultTenantBySlug')->once()->andReturnNull();
    $repository->shouldReceive('save')
        ->once()
        ->with(Mockery::type(Tenant::class))
        ->andReturnUsing(fn (Tenant $tenant) => $tenant);

    $useCase = new CreateTenantUseCase($repository, tenantUuidGeneratorStub());

    $tenant = $useCase->execute('mitienda', 'owomarket.test');

    expect($tenant)->toBeInstanceOf(Tenant::class);
    expect($tenant->getId()->value())->toBe(TENANT_UUID);
    expect($tenant->getName()->value())->toBe('mitienda');
    expect($tenant->getSlug()->value())->toBe('mitienda');
    expect($tenant->getCreatedAt())->not->toBeNull();
});

test('CreateTenantUseCase no lanza TypeError por falta del UuidGenerator', function () {
    $repository = Mockery::mock(TenantRepositoryInterface::class);
    $repository->shouldReceive('consultTenantBySlug')->andReturnNull();
    $repository->shouldReceive('save')->andReturnUsing(fn (Tenant $tenant) => $tenant);

    $useCase = new CreateTenantUseCase($repository, tenantUuidGeneratorStub());

    expect(fn () => $useCase->execute('otratienda', 'owomarket.test'))
        ->not->toThrow(TypeError::class);
});

test('CreateTenantUseCase lanza excepción 400 y no persiste cuando el slug ya está en uso', function () {
    $existing = Tenant::create(
        tenantUuidGeneratorStub(),
        TenantName::make('mitienda'),
        Slug::make('mitienda', 'owomarket.test'),
        Src\Tenant\Domain\ValueObjects\TenantStatus::active(),
        Src\Shared\Domain\ValueObjects\Timezone::make('UTC'),
        Src\Shared\Domain\ValueObjects\Currency::make('USD'),
        Src\Tenant\Domain\ValueObjects\TenantRequest::inProgress(),
    );

    $repository = Mockery::mock(TenantRepositoryInterface::class);
    $repository->shouldReceive('consultTenantBySlug')->once()->andReturn($existing);
    $repository->shouldNotReceive('save');

    $useCase = new CreateTenantUseCase($repository, tenantUuidGeneratorStub());

    expect(fn () => $useCase->execute('mitienda', 'owomarket.test'))
        ->toThrow(Exception::class, 'Slug already in use');
});
