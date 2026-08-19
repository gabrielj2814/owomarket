<?php

declare(strict_types=1);

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Application\UseCase\CreateAdminUseCase;
use Src\Admin\Domain\Entities\Admin;
use Src\Admin\Domain\ValueObjects\UserType;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;

/**
 * Regresión: Admin::create() exige UuidGenerator como primer parámetro.
 * Antes de la corrección, CreateAdminUseCase invocaba Admin::create($name, ...)
 * y lanzaba TypeError: Argument #1 ($generator) must be of type UuidGenerator.
 */
const ADMIN_UUID = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

function makeCreateAdminUseCase(AdminRepositoryInterface $repository): CreateAdminUseCase
{
    $validator = Mockery::mock(PasswordValidator::class);
    $validator->shouldReceive('validate')->andReturnNull();

    $hasher = Mockery::mock(PasswordHasher::class);
    $hasher->shouldReceive('hash')->andReturn('$2y$10$abcdefghijklmnopqrstuv');

    $generator = Mockery::mock(UuidGenerator::class);
    $generator->shouldReceive('generate')->andReturn(ADMIN_UUID);

    return new CreateAdminUseCase($repository, $validator, $hasher, $generator);
}

test('CreateAdminUseCase crea un admin con el UUID entregado por el generador inyectado', function () {
    $repository = Mockery::mock(AdminRepositoryInterface::class);
    $repository->shouldReceive('create')
        ->once()
        ->with(Mockery::type(Admin::class))
        ->andReturnUsing(fn (Admin $admin) => $admin);

    $useCase = makeCreateAdminUseCase($repository);

    $admin = $useCase->execute('Luisa Serra', 'luisa@owomarket.test', '04141234567', 'Password123!');

    expect($admin)->toBeInstanceOf(Admin::class);
    expect($admin->getId()->value())->toBe(ADMIN_UUID);
    expect($admin->getName()->value())->toBe('Luisa Serra');
    expect($admin->getEmail()->value())->toBe('luisa@owomarket.test');
    expect($admin->getType()->value())->toBe(UserType::SUPER_ADMIN);
});

test('CreateAdminUseCase no lanza TypeError por falta del UuidGenerator', function () {
    $repository = Mockery::mock(AdminRepositoryInterface::class);
    $repository->shouldReceive('create')->andReturnUsing(fn (Admin $admin) => $admin);

    $useCase = makeCreateAdminUseCase($repository);

    expect(fn () => $useCase->execute('Ana Perez', 'ana@owomarket.test', '04141234567', 'Password123!'))
        ->not->toThrow(TypeError::class);
});

test('CreateAdminUseCase persiste el admin una sola vez y con contraseña hasheada', function () {
    $persisted = null;

    $repository = Mockery::mock(AdminRepositoryInterface::class);
    $repository->shouldReceive('create')
        ->once()
        ->andReturnUsing(function (Admin $admin) use (&$persisted) {
            $persisted = $admin;

            return $admin;
        });

    $useCase = makeCreateAdminUseCase($repository);
    $useCase->execute('Luisa Serra', 'luisa@owomarket.test', '04141234567', 'Password123!');

    expect($persisted)->toBeInstanceOf(Admin::class);
    expect($persisted->getPassword()->getHash())->toBe('$2y$10$abcdefghijklmnopqrstuv');
});
