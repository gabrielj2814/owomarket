<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Billing\Domain\Entities\Invoice;
use Src\Billing\Domain\Entities\InvoiceItem;
use Src\Billing\Infrastructure\Mail\InvoiceMail;
use Src\Billing\Infrastructure\Services\DomPdfInvoiceGeneratorService;
use Src\Billing\Infrastructure\Services\LaravelInvoiceMailerService;
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
    if (! Schema::hasTable('invoice_items')) {
        (require base_path('database/migrations/tenant/2026_08_18_000003_create_invoice_items.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Billing Services Store',
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
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('DomPdfInvoiceGeneratorService generates valid binary PDF string', function () {
    $item = InvoiceItem::create('Consultoría Estratégica', 1, 350.00, 19.0);

    $invoice = Invoice::createDirect(
        invoiceNumber: 'FAC-2026-000100',
        customer: [
            'name' => 'Empresa Receptora SpA',
            'tax_id' => '77123456-7',
            'email' => 'contacto@receptora.cl',
            'address_line_1' => 'Calle Principal 100',
            'city' => 'Santiago',
            'state' => 'RM',
            'postal_code' => '8320000',
            'country' => 'Chile',
        ],
        issuer: [
            'legal_name' => 'Mi Tienda SpA',
            'tax_id' => '76111222-3',
            'billing_email' => 'facturacion@mitienda.cl',
            'address_line_1' => 'Av Comercial 400',
            'city' => 'Santiago',
            'state' => 'RM',
            'postal_code' => '8320000',
            'country' => 'Chile',
            'invoice_prefix' => 'FAC-',
        ],
        items: [$item]
    );

    $service = new DomPdfInvoiceGeneratorService;
    $pdfBinary = $service->generate($invoice);

    expect($pdfBinary)->toBeString()
        ->and($pdfBinary)->toStartWith('%PDF-');
});

it('LaravelInvoiceMailerService sends email with attached PDF', function () {
    Mail::fake();

    $item = InvoiceItem::create('Servicio A', 1, 100.00);
    $invoice = Invoice::createDirect(
        invoiceNumber: 'FAC-2026-000101',
        customer: [
            'name' => 'Cliente Mail',
            'email' => 'cliente@test.com',
            'address_line_1' => 'C1',
            'city' => 'C',
            'state' => 'S',
            'postal_code' => '1',
            'country' => 'Chile',
        ],
        issuer: [
            'legal_name' => 'Emisor',
            'tax_id' => '123',
            'billing_email' => 'e@test.com',
            'address_line_1' => 'E1',
            'city' => 'C',
            'state' => 'S',
            'postal_code' => '1',
            'country' => 'Chile',
            'invoice_prefix' => 'FAC-',
        ],
        items: [$item]
    );

    $pdfService = new DomPdfInvoiceGeneratorService;
    $mailerService = new LaravelInvoiceMailerService($pdfService);

    $result = $mailerService->sendInvoiceEmail($invoice);

    expect($result)->toBeTrue();

    Mail::assertSent(InvoiceMail::class, function ($mail) {
        return $mail->hasTo('cliente@test.com');
    });
});
