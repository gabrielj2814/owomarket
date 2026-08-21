<?php

declare(strict_types=1);

use Src\Billing\Application\Contracts\Repositories\BillingProfileRepositoryInterface;
use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;
use Src\Billing\Application\DTOs\CreateDirectInvoiceData;
use Src\Billing\Application\DTOs\FilterInvoicesCriteria;
use Src\Billing\Application\DTOs\InvoiceItemData;
use Src\Billing\Application\DTOs\PaginatedInvoicesResult;
use Src\Billing\Application\UseCases\CancelInvoiceUseCase;
use Src\Billing\Application\UseCases\ConsultInvoiceByIdUseCase;
use Src\Billing\Application\UseCases\CreateDirectInvoiceUseCase;
use Src\Billing\Application\UseCases\FilterInvoicesUseCase;
use Src\Billing\Domain\Entities\BillingProfile;
use Src\Billing\Domain\Entities\Invoice;
use Src\Billing\Domain\Exceptions\InvoiceNotFoundException;
use Src\Billing\Domain\ValueObjects\InvoiceId;

// Fase 1.3 (hallazgo C4): CreateDirectInvoiceUseCase corre dentro de una
// transacción para que un fallo al guardar la factura no consuma el
// correlativo. Eso necesita la aplicación levantada para resolver la fachada
// DB, así que este archivo se apoya en el TestCase de Laravel.
uses(Tests\TestCase::class);

it('CreateDirectInvoiceUseCase creates invoice and increments billing profile number', function () {
    $invRepo = Mockery::mock(InvoiceRepositoryInterface::class);
    $profileRepo = Mockery::mock(BillingProfileRepositoryInterface::class);

    $profile = BillingProfile::create(
        legalName: 'Tienda Test SpA',
        taxId: '76111222-3',
        billingEmail: 'billing@test.com',
        phone: '+56900000000',
        address: [
            'address_line_1' => 'Av Uno 123',
            'city' => 'Santiago',
            'state' => 'RM',
            'postal_code' => '8320000',
            'country' => 'Chile',
        ],
        invoicePrefix: 'FAC-',
        nextInvoiceNumber: 15
    );

    // El correlativo ya no se calcula en memoria: lo reserva el repositorio de
    // forma atómica, con la fila del perfil bloqueada (hallazgo C4).
    $year = date('Y');
    $profileRepo->shouldReceive('reserveNextInvoiceNumber')
        ->once()
        ->andReturn("FAC-{$year}-000015");
    $profileRepo->shouldReceive('getProfile')->once()->andReturn($profile);

    $invRepo->shouldReceive('save')
        ->once()
        ->with(Mockery::type(Invoice::class))
        ->andReturnUsing(fn (Invoice $inv) => $inv);

    $useCase = new CreateDirectInvoiceUseCase($invRepo, $profileRepo);

    $dto = new CreateDirectInvoiceData(
        customer_name: 'Comprador Uno',
        customer_email: 'comprador@gmail.com',
        customer_tax_id: '12345678-9',
        customer_address_line_1: 'Calle 10',
        customer_city: 'Santiago',
        customer_state: 'RM',
        customer_postal_code: '8320000',
        customer_country: 'Chile',
        items: [
            new InvoiceItemData(
                description: 'Producto Directo A',
                quantity: 2,
                unit_price: 150.00,
                tax_rate: 19.0
            ),
        ]
    );

    $invoice = $useCase->execute($dto);

    expect($invoice->invoiceNumber()->value())->toBe("FAC-{$year}-000015")
        ->and($invoice->customer()->name())->toBe('Comprador Uno')
        ->and($invoice->subtotal())->toBe(300.00)
        ->and($invoice->taxAmount())->toBe(57.00)
        ->and($invoice->total())->toBe(357.00);
    // El incremento del contador ya no ocurre aquí, sino dentro de
    // reserveNextInvoiceNumber(); se prueba en BillingInvoiceApiTest.
});

it('ConsultInvoiceByIdUseCase finds invoice or throws exception', function () {
    $invRepo = Mockery::mock(InvoiceRepositoryInterface::class);
    $invRepo->shouldReceive('findById')
        ->once()
        ->andReturn(null);

    $useCase = new ConsultInvoiceByIdUseCase($invRepo);

    expect(fn () => $useCase->execute('non-existing-id'))
        ->toThrow(InvoiceNotFoundException::class);
});

it('CancelInvoiceUseCase cancels invoice and saves it', function () {
    $invRepo = Mockery::mock(InvoiceRepositoryInterface::class);

    $invoice = Invoice::createDirect(
        invoiceNumber: 'FAC-2026-000099',
        customer: [
            'name' => 'Cliente',
            'email' => 'c@test.com',
            'address_line_1' => 'C1',
            'city' => 'C',
            'state' => 'S',
            'postal_code' => '1',
            'country' => 'P',
        ],
        issuer: [
            'legal_name' => 'Emisor',
            'tax_id' => '123',
            'billing_email' => 'e@test.com',
            'address_line_1' => 'E1',
            'city' => 'C',
            'state' => 'S',
            'postal_code' => '1',
            'country' => 'P',
        ],
        items: [
            \Src\Billing\Domain\Entities\InvoiceItem::create('Item', 1, 50.0),
        ]
    );

    $invRepo->shouldReceive('findById')
        ->once()
        ->with(Mockery::type(InvoiceId::class))
        ->andReturn($invoice);

    $invRepo->shouldReceive('update')
        ->once()
        ->with(Mockery::type(Invoice::class))
        ->andReturnUsing(fn (Invoice $i) => $i);

    $useCase = new CancelInvoiceUseCase($invRepo);
    $cancelled = $useCase->execute($invoice->id()->value(), 'Cliente solicitó anulación');

    expect($cancelled->status()->isCancelled())->toBeTrue();
});

it('FilterInvoicesUseCase delegates to repository filter', function () {
    $invRepo = Mockery::mock(InvoiceRepositoryInterface::class);
    $criteria = new FilterInvoicesCriteria;

    $paginatedResult = new PaginatedInvoicesResult([], 0, 1, 15, 1);

    $invRepo->shouldReceive('filter')
        ->once()
        ->with($criteria)
        ->andReturn($paginatedResult);

    $useCase = new FilterInvoicesUseCase($invRepo);
    $result = $useCase->execute($criteria);

    expect($result->total)->toBe(0)
        ->and($result->items)->toBeArray();
});
