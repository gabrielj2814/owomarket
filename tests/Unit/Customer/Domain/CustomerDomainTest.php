<?php

declare(strict_types=1);

use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Domain\Entities\CustomerAddress;
use Src\Customer\Domain\Exceptions\CustomerAddressNotFoundException;
use Src\Customer\Domain\ValueObjects\AddressId;
use Src\Customer\Domain\ValueObjects\BirthDate;
use Src\Customer\Domain\ValueObjects\CustomerEmail;
use Src\Customer\Domain\ValueObjects\CustomerName;

it('creates Customer with valid domain attributes', function () {
    $customer = Customer::create(
        name: 'Carlos Mendoza',
        email: 'carlos@empresa.com',
        phone: '+56912345678',
        birthDate: '1990-05-15',
        gender: 'male',
        isActive: true,
        acceptsMarketing: true,
        metadata: ['source' => 'web_registration']
    );

    expect($customer->id()->value())->toBeString()
        ->and($customer->name()->value())->toBe('Carlos Mendoza')
        ->and($customer->email()->value())->toBe('carlos@empresa.com')
        ->and($customer->phone()?->value())->toBe('+56912345678')
        ->and($customer->birthDate()?->value())->toBe('1990-05-15')
        ->and($customer->gender()?->value())->toBe('male')
        ->and($customer->isActive())->toBeTrue()
        ->and($customer->acceptsMarketing())->toBeTrue()
        ->and($customer->metadata())->toBe(['source' => 'web_registration']);
});

it('validates CustomerEmail format and normalization', function () {
    $email = CustomerEmail::fromString('  CARLOS@EMPRESA.COM  ');
    expect($email->value())->toBe('carlos@empresa.com');

    expect(fn () => CustomerEmail::fromString('not-an-email'))
        ->toThrow(InvalidArgumentException::class);
});

it('validates CustomerName length constraints', function () {
    expect(fn () => CustomerName::fromString('A'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => CustomerName::fromString(''))
        ->toThrow(InvalidArgumentException::class);
});

it('validates BirthDate format and rejects future dates', function () {
    $validDate = BirthDate::fromString('1985-12-01');
    expect($validDate->value())->toBe('1985-12-01');

    expect(fn () => BirthDate::fromString('invalid-date'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => BirthDate::fromString('2099-01-01'))
        ->toThrow(InvalidArgumentException::class);
});

it('updates Customer profile and toggles status', function () {
    $customer = Customer::create(
        name: 'Maria Paz',
        email: 'maria@test.com'
    );

    $customer->updateProfile(
        name: 'Maria Paz Gomez',
        email: 'maria.gomez@test.com',
        phone: '+56988887777',
        birthDate: '1995-10-20',
        gender: 'female',
        isActive: true,
        acceptsMarketing: true,
        metadata: ['vip' => true]
    );

    expect($customer->name()->value())->toBe('Maria Paz Gomez')
        ->and($customer->email()->value())->toBe('maria.gomez@test.com')
        ->and($customer->gender()?->value())->toBe('female')
        ->and($customer->metadata())->toBe(['vip' => true]);

    $customer->deactivate();
    expect($customer->isActive())->toBeFalse();

    $customer->activate();
    expect($customer->isActive())->toBeTrue();
});

it('manages customer addresses collection and default addresses properly', function () {
    $customer = Customer::create(
        name: 'Cliente Con Direcciones',
        email: 'direcciones@test.com'
    );

    $address1 = CustomerAddress::create(
        firstName: 'Cliente',
        lastName: 'Uno',
        addressLine1: 'Av. Providencia 100',
        city: 'Santiago',
        state: 'RM',
        postalCode: '7500000',
        country: 'Chile',
        type: 'shipping',
        isDefault: true
    );

    $address2 = CustomerAddress::create(
        firstName: 'Cliente',
        lastName: 'Dos',
        addressLine1: 'Av. Apoquindo 200',
        city: 'Las Condes',
        state: 'RM',
        postalCode: '7550000',
        country: 'Chile',
        type: 'shipping',
        isDefault: false
    );

    $customer->addAddress($address1);
    $customer->addAddress($address2);

    expect($customer->addresses())->toHaveCount(2)
        ->and($customer->getDefaultAddress()?->id()->equals($address1->id()))->toBeTrue();

    // Cambiar dirección por defecto a address2
    $customer->setDefaultAddress($address2->id());
    expect($address1->isDefault())->toBeFalse()
        ->and($address2->isDefault())->toBeTrue()
        ->and($customer->getDefaultAddress()?->id()->equals($address2->id()))->toBeTrue();

    // Eliminar address1
    $customer->removeAddress($address1->id());
    expect($customer->addresses())->toHaveCount(1);

    // Error al eliminar dirección inexistente
    expect(fn () => $customer->removeAddress(AddressId::random()))
        ->toThrow(CustomerAddressNotFoundException::class);
});
