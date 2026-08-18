<?php

declare(strict_types=1);

use Src\Customer\Domain\Entities\CustomerAddress;
use Src\Customer\Domain\ValueObjects\AddressId;

it('creates CustomerAddress with valid attributes', function () {
    $address = CustomerAddress::create(
        firstName: 'Juan',
        lastName: 'Perez',
        addressLine1: 'Los Leones 123',
        city: 'Providencia',
        state: 'RM',
        postalCode: '7510000',
        country: 'Chile',
        type: 'billing',
        addressLine2: 'Depto 402',
        company: 'Empresa SA',
        phone: '+56911223344',
        isDefault: true
    );

    expect($address->id())->toBeInstanceOf(AddressId::class)
        ->and($address->type()->value())->toBe('billing')
        ->and($address->type()->isBilling())->toBeTrue()
        ->and($address->firstName())->toBe('Juan')
        ->and($address->lastName())->toBe('Perez')
        ->and($address->fullName())->toBe('Juan Perez')
        ->and($address->addressLine1())->toBe('Los Leones 123')
        ->and($address->addressLine2())->toBe('Depto 402')
        ->and($address->company())->toBe('Empresa SA')
        ->and($address->isDefault())->toBeTrue();
});

it('updates CustomerAddress properties and rejects invalid empty fields', function () {
    $address = CustomerAddress::create(
        firstName: 'Juan',
        lastName: 'Perez',
        addressLine1: 'Calle A 10',
        city: 'Santiago',
        state: 'RM',
        postalCode: '8320000',
        country: 'Chile'
    );

    $address->update(
        firstName: 'Juan Carlos',
        lastName: 'Perez Diaz',
        addressLine1: 'Calle B 20',
        city: 'Concepción',
        state: 'Biobío',
        postalCode: '4030000',
        country: 'Chile',
        type: 'shipping',
        addressLine2: 'Casa 3',
        company: null,
        phone: '+56999887766',
        isDefault: false
    );

    expect($address->firstName())->toBe('Juan Carlos')
        ->and($address->city())->toBe('Concepción')
        ->and($address->postalCode())->toBe('4030000');

    expect(fn () => $address->update('', 'Perez', 'Calle B', 'C', 'S', '1', 'Chile', 'shipping'))
        ->toThrow(InvalidArgumentException::class);
});
