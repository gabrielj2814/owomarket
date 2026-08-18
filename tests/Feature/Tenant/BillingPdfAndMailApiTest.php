<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Billing\Infrastructure\Mail\InvoiceMail;
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
        'name' => 'Billing PDF API Store',
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

it('GET /api-tenant/billing/invoices/{id}/pdf returns binary PDF stream with 200', function () {
    $createResponse = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices", [
        'customer_name' => 'Cliente PDF',
        'customer_email' => 'pdf@test.com',
        'customer_address_line_1' => 'Calle 100',
        'customer_city' => 'Santiago',
        'customer_state' => 'RM',
        'customer_postal_code' => '8320000',
        'customer_country' => 'Chile',
        'items' => [
            ['description' => 'Servicio Impreso', 'quantity' => 1, 'unit_price' => 100.00],
        ],
    ]);

    $invoiceId = $createResponse->json('data.id');

    $pdfResponse = $this->get("http://{$this->domain}/api-tenant/billing/invoices/{$invoiceId}/pdf");

    $pdfResponse->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf');

    expect($pdfResponse->getContent())->toStartWith('%PDF-');
});

it('POST /api-tenant/billing/invoices/{id}/resend-email resends email with invoice attached', function () {
    Mail::fake();

    $createResponse = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices", [
        'customer_name' => 'Cliente Reenvio',
        'customer_email' => 'reenvio@test.com',
        'customer_address_line_1' => 'Calle 200',
        'customer_city' => 'Santiago',
        'customer_state' => 'RM',
        'customer_postal_code' => '8320000',
        'customer_country' => 'Chile',
        'items' => [
            ['description' => 'Producto Entregado', 'quantity' => 2, 'unit_price' => 50.00],
        ],
    ]);

    $invoiceId = $createResponse->json('data.id');

    $resendResponse = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices/{$invoiceId}/resend-email", [
        'email' => 'otro_correo@test.com',
    ]);

    $resendResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');

    Mail::assertSent(InvoiceMail::class, function ($mail) {
        return $mail->hasTo('otro_correo@test.com');
    });
});
