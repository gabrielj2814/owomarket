<?php

declare(strict_types=1);

use Src\Billing\Domain\Entities\Invoice;
use Src\Billing\Domain\Entities\InvoiceItem;
use Src\Billing\Domain\Exceptions\InvalidInvoiceStateException;

it('creates InvoiceItem and calculates line totals accurately', function () {
    $item = InvoiceItem::create(
        description: 'Desarrollo Web Pro',
        quantity: 2,
        unitPrice: 500.00,
        taxRate: 19.0, // 19% IVA
        discountAmount: 100.00 // $100 descuento
    );

    // subtotal = 2 * 500 = 1000
    // taxable = 1000 - 100 = 900
    // tax = 900 * 0.19 = 171.00
    // total = 900 + 171 = 1071.00
    expect($item->subtotal())->toBe(1000.00)
        ->and($item->taxAmount())->toBe(171.00)
        ->and($item->discountAmount())->toBe(100.00)
        ->and($item->total())->toBe(1071.00);
});

it('creates Invoice aggregate root with direct items and sums totals properly', function () {
    $item1 = InvoiceItem::create(
        description: 'Servicio A',
        quantity: 1,
        unitPrice: 100.00,
        taxRate: 10.0,
        discountAmount: 0.0
    );

    $item2 = InvoiceItem::create(
        description: 'Servicio B',
        quantity: 2,
        unitPrice: 50.00,
        taxRate: 10.0,
        discountAmount: 10.0
    );

    $invoice = Invoice::createDirect(
        invoiceNumber: 'FAC-2026-000001',
        customer: [
            'name' => 'Cliente Test',
            'tax_id' => '12345678-9',
            'email' => 'cliente@test.com',
            'address_line_1' => 'Calle 1',
            'city' => 'Ciudad',
            'state' => 'Estado',
            'postal_code' => '1000',
            'country' => 'Pais',
        ],
        issuer: [
            'legal_name' => 'Emisor SpA',
            'tax_id' => '99888777-6',
            'billing_email' => 'emisor@store.com',
            'address_line_1' => 'Av Central',
            'city' => 'Capital',
            'state' => 'Región',
            'postal_code' => '2000',
            'country' => 'Pais',
            'invoice_prefix' => 'FAC-',
        ],
        items: [$item1, $item2],
        paymentMethod: 'manual',
        paymentStatus: 'paid'
    );

    expect($invoice->invoiceNumber()->value())->toBe('FAC-2026-000001')
        ->and($invoice->status()->isIssued())->toBeTrue()
        ->and($invoice->subtotal())->toBe(200.00)
        ->and($invoice->discountAmount())->toBe(10.00)
        ->and($invoice->taxAmount())->toBe(19.00) // (100 * 0.1) + (90 * 0.1) = 10 + 9 = 19
        ->and($invoice->total())->toBe(209.00) // 110 + 99 = 209
        ->and($invoice->items())->toHaveCount(2);
});

it('allows cancelling an issued invoice and prevents cancelling an already cancelled one', function () {
    $item = InvoiceItem::create('Item 1', 1, 100.00);

    $invoice = Invoice::createDirect(
        invoiceNumber: 'FAC-2026-000005',
        customer: [
            'name' => 'Cliente',
            'email' => 'cliente@test.com',
            'address_line_1' => 'Calle 1',
            'city' => 'Ciudad',
            'state' => 'Estado',
            'postal_code' => '1000',
            'country' => 'Pais',
        ],
        issuer: [
            'legal_name' => 'Emisor',
            'tax_id' => '112233',
            'billing_email' => 'emisor@test.com',
            'address_line_1' => 'Calle 2',
            'city' => 'Ciudad',
            'state' => 'Estado',
            'postal_code' => '1000',
            'country' => 'Pais',
        ],
        items: [$item]
    );

    expect($invoice->status()->isIssued())->toBeTrue();

    $invoice->cancel('Error en datos del cliente');
    expect($invoice->status()->isCancelled())->toBeTrue()
        ->and($invoice->notes())->toContain('Anulada: Error en datos del cliente');

    expect(fn () => $invoice->cancel())
        ->toThrow(InvalidInvoiceStateException::class);
});
