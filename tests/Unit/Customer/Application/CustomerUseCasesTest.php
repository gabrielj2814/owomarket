<?php

declare(strict_types=1);

use Src\Customer\Application\Contracts\Repositories\CustomerRepositoryInterface;
use Src\Customer\Application\DTOs\CreateCustomerData;
use Src\Customer\Application\DTOs\CustomerAddressInputData;
use Src\Customer\Application\DTOs\CustomerMetricsData;
use Src\Customer\Application\DTOs\FilterCustomersCriteria;
use Src\Customer\Application\DTOs\PaginatedCustomerResult;
use Src\Customer\Application\DTOs\UpdateCustomerData;
use Src\Customer\Application\UseCases\AddCustomerAddressUseCase;
use Src\Customer\Application\UseCases\ConsultCustomerByIdUseCase;
use Src\Customer\Application\UseCases\CreateCustomerUseCase;
use Src\Customer\Application\UseCases\DeleteCustomerAddressUseCase;
use Src\Customer\Application\UseCases\DeleteCustomerUseCase;
use Src\Customer\Application\UseCases\FilterCustomersUseCase;
use Src\Customer\Application\UseCases\GetCustomerMetricsUseCase;
use Src\Customer\Application\UseCases\SetDefaultCustomerAddressUseCase;
use Src\Customer\Application\UseCases\UpdateCustomerUseCase;
use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Domain\Exceptions\CustomerNotFoundException;
use Src\Customer\Domain\Exceptions\DuplicateCustomerEmailException;
use Src\Customer\Domain\ValueObjects\CustomerEmail;
use Src\Customer\Domain\ValueObjects\CustomerId;

it('CreateCustomerUseCase creates new customer and saves in repository', function () {
    $repo = Mockery::mock(CustomerRepositoryInterface::class);

    $repo->shouldReceive('findByEmail')
        ->once()
        ->with(Mockery::on(fn (CustomerEmail $email) => $email->value() === 'pedro@test.com'))
        ->andReturnNull();

    $repo->shouldReceive('save')
        ->once()
        ->with(Mockery::on(fn (Customer $c) => $c->email()->value() === 'pedro@test.com'));

    $useCase = new CreateCustomerUseCase($repo);

    $dto = new CreateCustomerData(
        name: 'Pedro Pascal',
        email: 'pedro@test.com',
        phone: '+56911223344',
        addresses: [
            new CustomerAddressInputData(
                first_name: 'Pedro',
                last_name: 'Pascal',
                address_line_1: 'Av. Providencia 100',
                city: 'Santiago',
                state: 'RM',
                postal_code: '7500000',
                country: 'Chile',
                is_default: true
            ),
        ]
    );

    $customer = $useCase->execute($dto);

    expect($customer->name()->value())->toBe('Pedro Pascal')
        ->and($customer->email()->value())->toBe('pedro@test.com')
        ->and($customer->addresses())->toHaveCount(1);
});

it('CreateCustomerUseCase throws exception on duplicate email', function () {
    $repo = Mockery::mock(CustomerRepositoryInterface::class);
    $existing = Customer::create(name: 'Existente', email: 'existe@test.com');

    $repo->shouldReceive('findByEmail')
        ->once()
        ->andReturn($existing);

    $useCase = new CreateCustomerUseCase($repo);

    $dto = new CreateCustomerData(
        name: 'Duplicado',
        email: 'existe@test.com'
    );

    expect(fn () => $useCase->execute($dto))
        ->toThrow(DuplicateCustomerEmailException::class);
});

it('ConsultCustomerByIdUseCase returns customer or throws exception', function () {
    $repo = Mockery::mock(CustomerRepositoryInterface::class);
    $customer = Customer::create(name: 'Juan', email: 'juan@test.com');

    $repo->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (CustomerId $id) => $id->equals($customer->id())))
        ->andReturn($customer);

    $useCase = new ConsultCustomerByIdUseCase($repo);
    $result = $useCase->execute($customer->id()->value());

    expect($result->name()->value())->toBe('Juan');

    // Caso no encontrado
    $repo->shouldReceive('findById')
        ->once()
        ->andReturnNull();

    expect(fn () => $useCase->execute('00000000-0000-0000-0000-000000000000'))
        ->toThrow(CustomerNotFoundException::class);
});

it('FilterCustomersUseCase delegates criteria to repository', function () {
    $repo = Mockery::mock(CustomerRepositoryInterface::class);
    $expected = new PaginatedCustomerResult([], 0, 1, 15, 1);

    $criteria = new FilterCustomersCriteria(search: 'Mendoza', is_active: true);

    $repo->shouldReceive('filter')
        ->once()
        ->with($criteria)
        ->andReturn($expected);

    $useCase = new FilterCustomersUseCase($repo);
    $result = $useCase->execute($criteria);

    expect($result->total)->toBe(0);
});

it('UpdateCustomerUseCase modifies customer and checks email duplication', function () {
    $repo = Mockery::mock(CustomerRepositoryInterface::class);
    $customer = Customer::create(name: 'Original', email: 'original@test.com');

    $repo->shouldReceive('findById')
        ->once()
        ->andReturn($customer);

    $repo->shouldReceive('findByEmail')
        ->once()
        ->with(Mockery::on(fn (CustomerEmail $e) => $e->value() === 'nuevo@test.com'))
        ->andReturnNull();

    $repo->shouldReceive('save')
        ->once()
        ->with(Mockery::on(fn (Customer $c) => $c->email()->value() === 'nuevo@test.com'));

    $useCase = new UpdateCustomerUseCase($repo);

    $dto = new UpdateCustomerData(
        name: 'Actualizado',
        email: 'nuevo@test.com',
        is_active: false
    );

    $updated = $useCase->execute($customer->id()->value(), $dto);

    expect($updated->name()->value())->toBe('Actualizado')
        ->and($updated->email()->value())->toBe('nuevo@test.com')
        ->and($updated->isActive())->toBeFalse();
});

it('DeleteCustomerUseCase deletes customer through repository', function () {
    $repo = Mockery::mock(CustomerRepositoryInterface::class);
    $customer = Customer::create(name: 'A Eliminar', email: 'del@test.com');

    $repo->shouldReceive('findById')
        ->once()
        ->andReturn($customer);

    $repo->shouldReceive('delete')
        ->once()
        ->with(Mockery::on(fn (CustomerId $id) => $id->equals($customer->id())));

    $useCase = new DeleteCustomerUseCase($repo);
    $useCase->execute($customer->id()->value());
});

it('AddCustomerAddressUseCase, DeleteCustomerAddressUseCase and SetDefaultCustomerAddressUseCase', function () {
    $repo = Mockery::mock(CustomerRepositoryInterface::class);
    $customer = Customer::create(name: 'Cliente', email: 'cli@test.com');

    $repo->shouldReceive('findById')
        ->times(3)
        ->andReturn($customer);

    $repo->shouldReceive('save')
        ->times(3);

    // 1. Agregar dirección
    $addUseCase = new AddCustomerAddressUseCase($repo);
    $addressDto = new CustomerAddressInputData(
        first_name: 'Cliente',
        last_name: 'Test',
        address_line_1: 'Calle 1',
        city: 'Santiago',
        state: 'RM',
        postal_code: '123',
        country: 'Chile',
        is_default: true
    );
    $res = $addUseCase->execute($customer->id()->value(), $addressDto);
    expect($res->addresses())->toHaveCount(1);

    $addrId = $res->addresses()[0]->id()->value();

    // 2. Establecer por defecto
    $setDefaultUseCase = new SetDefaultCustomerAddressUseCase($repo);
    $res2 = $setDefaultUseCase->execute($customer->id()->value(), $addrId);
    expect($res2->addresses()[0]->isDefault())->toBeTrue();

    // 3. Eliminar dirección
    $deleteAddrUseCase = new DeleteCustomerAddressUseCase($repo);
    $res3 = $deleteAddrUseCase->execute($customer->id()->value(), $addrId);
    expect($res3->addresses())->toHaveCount(0);
});

it('GetCustomerMetricsUseCase returns metrics from repository', function () {
    $repo = Mockery::mock(CustomerRepositoryInterface::class);
    $expectedMetrics = new CustomerMetricsData(
        total_customers: 100,
        active_customers: 95,
        marketing_subscribers: 40,
        new_this_month: 12
    );

    $repo->shouldReceive('getMetrics')
        ->once()
        ->andReturn($expectedMetrics);

    $useCase = new GetCustomerMetricsUseCase($repo);
    $metrics = $useCase->execute();

    expect($metrics->total_customers)->toBe(100)
        ->and($metrics->active_customers)->toBe(95);
});
