<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Billing\Application\DTOs\FilterInvoicesCriteria;
use Src\Billing\Domain\Entities\Invoice;
use Src\Billing\Domain\Entities\InvoiceItem;
use Src\Billing\Domain\ValueObjects\InvoiceNumber;
use Src\Billing\Infrastructure\Eloquent\Repositories\EloquentInvoiceRepository;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('billing_profiles')) {
        (require base_path('database/migrations/tenant/2026_08_18_000001_create_billing_profiles.php'))->up();
    }
    if (! Schema::hasTable('invoices')) {
        (require base_path('database/migrations/tenant/2026_08_18_000002_create_invoices.php'))->up();
    }
    if (! Schema::hasColumn('invoices', 'exchange_rate')) {
        (require base_path('database/migrations/tenant/2026_08_19_000008_add_exchange_rate_and_dual_totals_to_invoices_table.php'))->up();
    }
    if (! Schema::hasTable('invoice_items')) {
        (require base_path('database/migrations/tenant/2026_08_18_000003_create_invoice_items.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Billing Repository Store',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);

    tenancy()->initialize($this->tenant);
    $this->repository = new EloquentInvoiceRepository;
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('saves and retrieves invoice with items in tenant database', function () {
    $item1 = InvoiceItem::create('Consultoría TI', 2, 250.00, 19.0);
    $item2 = InvoiceItem::create('Licencia Software', 1, 100.00, 0.0);

    $invoice = Invoice::createDirect(
        invoiceNumber: 'FAC-2026-000010',
        customer: [
            'name' => 'Empresa Cliente SpA',
            'tax_id' => '77888999-1',
            'email' => 'contacto@cliente.cl',
            'address_line_1' => 'Av Providencia 1234',
            'city' => 'Santiago',
            'state' => 'RM',
            'postal_code' => '7500000',
            'country' => 'Chile',
        ],
        issuer: [
            'legal_name' => 'Mi Empresa SpA',
            'tax_id' => '76111222-3',
            'billing_email' => 'facturacion@miempresa.cl',
            'address_line_1' => 'Av Apoquindo 5000',
            'city' => 'Las Condes',
            'state' => 'RM',
            'postal_code' => '7550000',
            'country' => 'Chile',
            'invoice_prefix' => 'FAC-',
        ],
        items: [$item1, $item2]
    );

    $saved = $this->repository->save($invoice);

    expect($saved->invoiceNumber()->value())->toBe('FAC-2026-000010')
        ->and($saved->subtotal())->toBe(600.00)
        ->and($saved->taxAmount())->toBe(95.00)
        ->and($saved->total())->toBe(695.00)
        ->and($saved->items())->toHaveCount(2);

    $retrievedById = $this->repository->findById($saved->id());
    expect($retrievedById)->not->toBeNull()
        ->and($retrievedById->invoiceNumber()->value())->toBe('FAC-2026-000010')
        ->and($retrievedById->customer()->name())->toBe('Empresa Cliente SpA')
        ->and($retrievedById->items())->toHaveCount(2);

    $retrievedByNumber = $this->repository->findByNumber(InvoiceNumber::fromString('FAC-2026-000010'));
    expect($retrievedByNumber)->not->toBeNull()
        ->and($retrievedByNumber->id()->value())->toBe($saved->id()->value());
});

it('filters invoices and calculates metrics correctly', function () {
    $item1 = InvoiceItem::create('Servicio General 1', 1, 100.00);
    $item2 = InvoiceItem::create('Servicio General 2', 1, 150.00);

    $invoice1 = Invoice::createDirect(
        invoiceNumber: 'FAC-2026-000001',
        customer: ['name' => 'Juan Perez', 'email' => 'juan@gmail.com', 'address_line_1' => 'Calle 1', 'city' => 'Santiago', 'state' => 'RM', 'postal_code' => '1000', 'country' => 'Chile'],
        issuer: ['legal_name' => 'Emisor', 'tax_id' => '123', 'billing_email' => 'e@test.com', 'address_line_1' => 'E1', 'city' => 'C', 'state' => 'S', 'postal_code' => '1', 'country' => 'Chile', 'invoice_prefix' => 'FAC-'],
        items: [$item1],
        status: 'paid'
    );
    $this->repository->save($invoice1);

    $invoice2 = Invoice::createDirect(
        invoiceNumber: 'FAC-2026-000002',
        customer: ['name' => 'Maria Lopez', 'email' => 'maria@gmail.com', 'address_line_1' => 'Calle 2', 'city' => 'Valparaiso', 'state' => 'V', 'postal_code' => '2000', 'country' => 'Chile'],
        issuer: ['legal_name' => 'Emisor', 'tax_id' => '123', 'billing_email' => 'e@test.com', 'address_line_1' => 'E1', 'city' => 'C', 'state' => 'S', 'postal_code' => '1', 'country' => 'Chile', 'invoice_prefix' => 'FAC-'],
        items: [$item2],
        status: 'cancelled'
    );
    $this->repository->save($invoice2);

    $filterResult = $this->repository->filter(new FilterInvoicesCriteria(search: 'Juan'));
    expect($filterResult->total)->toBe(1)
        ->and($filterResult->items[0]->customer()->name())->toBe('Juan Perez');

    $metrics = $this->repository->getMetrics();
    expect($metrics['total_paid'])->toBe(1)
        ->and($metrics['total_cancelled'])->toBe(1)
        ->and($metrics['total_billed'])->toBe(100.00);
});

it('saves and retrieves invoice with multi-currency and BCV exchange rate data', function () {
    $item = InvoiceItem::create('Zapato Deportivo', 1, 50.00, 16.0);

    $invoice = Invoice::createDirect(
        invoiceNumber: 'FAC-2026-000555',
        customer: ['name' => 'Comprador VE', 'email' => 'comprador@ve.com', 'address_line_1' => 'C1', 'city' => 'Caracas', 'state' => 'Miranda', 'postal_code' => '1060', 'country' => 'Venezuela'],
        issuer: ['legal_name' => 'Tienda VE', 'tax_id' => 'J-12345678-0', 'billing_email' => 'tienda@ve.com', 'address_line_1' => 'E1', 'city' => 'Caracas', 'state' => 'Miranda', 'postal_code' => '1060', 'country' => 'Venezuela', 'invoice_prefix' => 'FAC-'],
        items: [$item],
        currency: 'USD',
        exchangeRate: 775.3356,
        commissionAmount: 2.50,
        commissionCurrency: 'USDT'
    );

    $saved = $this->repository->save($invoice);

    expect($saved->exchangeRate())->toBe(775.3356)
        ->and($saved->totalUsd())->toBe(58.00)
        ->and($saved->totalVes())->toBe(44969.46)
        ->and($saved->commissionAmount())->toBe(2.50)
        ->and($saved->commissionCurrency())->toBe('USDT');

    $retrieved = $this->repository->findById($saved->id());
    expect($retrieved)->not->toBeNull()
        ->and($retrieved->exchangeRate())->toBe(775.3356)
        ->and($retrieved->totalVes())->toBe(44969.46)
        ->and($retrieved->commissionCurrency())->toBe('USDT');
});
