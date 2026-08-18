<?php

declare(strict_types=1);

use Src\Billing\Domain\Entities\BillingProfile;
use Src\Billing\Domain\ValueObjects\BillingAddress;
use Src\Billing\Domain\ValueObjects\BillingEmail;
use Src\Billing\Domain\ValueObjects\TaxId;

it('creates and validates TaxId correctly', function () {
    $tax = TaxId::fromString('cl-12345678-9');
    expect($tax->value())->toBe('CL-12345678-9');

    expect(fn () => TaxId::fromString(''))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => TaxId::fromString('ab'))
        ->toThrow(InvalidArgumentException::class);
});

it('creates and validates BillingEmail correctly', function () {
    $email = BillingEmail::fromString('FACTURACION@STORE.COM');
    expect($email->value())->toBe('facturacion@store.com');

    expect(fn () => BillingEmail::fromString('invalid-email'))
        ->toThrow(InvalidArgumentException::class);
});

it('creates and validates BillingAddress correctly', function () {
    $address = BillingAddress::fromArray([
        'address_line_1' => 'Av. Principal 123',
        'city' => 'Santiago',
        'state' => 'RM',
        'postal_code' => '8320000',
        'country' => 'Chile',
    ]);

    expect($address->addressLine1())->toBe('Av. Principal 123')
        ->and($address->city())->toBe('Santiago')
        ->and($address->country())->toBe('Chile')
        ->and($address->fullFormattedAddress())->toContain('Av. Principal 123');

    expect(fn () => BillingAddress::fromArray(['address_line_1' => '', 'city' => '', 'country' => '']))
        ->toThrow(InvalidArgumentException::class);
});

it('creates BillingProfile entity and increments invoice correlatives atomically', function () {
    $profile = BillingProfile::create(
        legalName: 'Mi Tienda SpA',
        taxId: '76123456-7',
        billingEmail: 'admin@mitienda.cl',
        phone: '+56912345678',
        address: [
            'address_line_1' => 'Calle Comercial 456',
            'city' => 'Santiago',
            'state' => 'RM',
            'postal_code' => '8320000',
            'country' => 'Chile',
        ],
        invoicePrefix: 'FAC-',
        nextInvoiceNumber: 1
    );

    expect($profile->legalName())->toBe('Mi Tienda SpA')
        ->and($profile->taxId()->value())->toBe('76123456-7')
        ->and($profile->nextInvoiceNumber())->toBe(1);

    $invNumber1 = $profile->generateAndIncrementInvoiceNumber();
    $year = date('Y');
    expect($invNumber1)->toBe("FAC-{$year}-000001")
        ->and($profile->nextInvoiceNumber())->toBe(2);

    $invNumber2 = $profile->generateAndIncrementInvoiceNumber();
    expect($invNumber2)->toBe("FAC-{$year}-000002")
        ->and($profile->nextInvoiceNumber())->toBe(3);
});
