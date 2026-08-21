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
        'name' => 'Billing Complete Store',
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

    // Fase 0.3-E: /api-tenant/* dejó de estar abierto (hallazgo A5). Las rutas
    // de backoffice exigen ahora sesión de usuario de la tienda; se autentica
    // aquí para todo el archivo.
    $this->tenantUser = \Src\Tenant\Infrastructure\Eloquent\Models\User::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'name' => 'Store Staff',
        'email' => 'staff_'.bin2hex(random_bytes(5)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);
    $this->actingAs($this->tenantUser);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('executes full billing and payment lifecycle end-to-end', function () {
    Mail::fake();

    // 1. Configurar Perfil Fiscal del Tenant
    $profileData = [
        'legal_name' => 'Empresa de Prueba SpA',
        'tax_id' => '76.999.888-1',
        'billing_email' => 'facturacion@tiendaprueba.cl',
        'phone' => '+56 9 9876 5432',
        'address_line_1' => 'Av. Andrés Bello 2457, Of 1201',
        'city' => 'Providencia',
        'state' => 'Región Metropolitana',
        'postal_code' => '7510000',
        'country' => 'Chile',
        'invoice_prefix' => 'FAC-E2E-',
        'next_invoice_number' => 101,
        'invoice_footer_notes' => 'Condición de pago: Contado 30 días.',
    ];

    $updateProfileResponse = $this->putJson("http://{$this->domain}/api-tenant/billing/profile", $profileData);
    $updateProfileResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.legal_name', 'Empresa de Prueba SpA')
        ->assertJsonPath('data.invoice_prefix', 'FAC-E2E-')
        ->assertJsonPath('data.next_invoice_number', 101);

    // 2. Consultar Pasarelas de Pago Disponibles
    $gatewaysResponse = $this->getJson("http://{$this->domain}/api-tenant/payment/gateways");
    $gatewaysResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');

    $gateways = collect($gatewaysResponse->json('data'))->pluck('identifier')->all();
    expect($gateways)->toContain('manual_transfer')
        ->and($gateways)->toContain('cash_on_delivery');

    // 3. Procesar un Pago mediante la pasarela Manual
    $paymentResponse = $this->postJson("http://{$this->domain}/api-tenant/payment/process", [
        'amount' => 595.00,
        'currency' => 'USD',
        'customer_email' => 'comprador@cliente.com',
        'customer_name' => 'Comprador Final',
        'payment_method' => 'manual_transfer',
        'order_id' => 'ORD-E2E-001',
        'description' => 'Compra de productos de prueba',
    ]);
    $paymentResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.success', true);

    // 4. Emitir Factura Directa / Mostrador con 2 conceptos, descuento e IVA 19%
    $invoicePayload = [
        'customer_name' => 'Cliente Receptor SpA',
        'customer_tax_id' => '77.333.444-5',
        'customer_email' => 'contacto@receptor.cl',
        'customer_address_line_1' => 'Calle Los Aromos 500',
        'customer_city' => 'Santiago',
        'customer_state' => 'RM',
        'customer_postal_code' => '8320000',
        'customer_country' => 'Chile',
        'payment_method' => 'manual_transfer',
        'payment_status' => 'paid',
        'currency' => 'USD',
        'notes' => 'Factura emitida directamente desde mostrador.',
        'items' => [
            [
                'description' => 'Laptop Gamer Pro',
                'quantity' => 1,
                'unit_price' => 1000.00,
                'tax_rate' => 19.0,
                'discount_amount' => 100.00, // Imponible: 900 -> IVA: 171 -> Total: 1071
                'sku' => 'LAP-001',
            ],
            [
                'description' => 'Mouse Inalámbrico RGB',
                'quantity' => 2,
                'unit_price' => 50.00,
                'tax_rate' => 19.0,
                'discount_amount' => 0.00, // Imponible: 100 -> IVA: 19 -> Total: 119
                'sku' => 'MOU-002',
            ],
        ],
    ];

    $createInvoiceResponse = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices", $invoicePayload);
    $createInvoiceResponse->assertStatus(201)
        ->assertJsonPath('status', 'success');

    $invoiceData = $createInvoiceResponse->json('data');
    $invoiceId = $invoiceData['id'];

    expect($invoiceData['invoice_number'])->toBe('FAC-E2E-2026-000101')
        ->and((float) $invoiceData['subtotal'])->toEqual(1100.00)
        ->and((float) $invoiceData['discount_amount'])->toEqual(100.00)
        ->and((float) $invoiceData['tax_amount'])->toEqual(190.00)
        ->and((float) $invoiceData['total'])->toEqual(1190.00)
        ->and($invoiceData['status'])->toBe('issued');

    // 5. Verificar que el perfil fiscal incrementó su correlativo automáticamente
    $getProfileResponse = $this->getJson("http://{$this->domain}/api-tenant/billing/profile");
    $getProfileResponse->assertStatus(200)
        ->assertJsonPath('data.next_invoice_number', 102);

    // 6. Consultar la factura por ID
    $consultInvoiceResponse = $this->getJson("http://{$this->domain}/api-tenant/billing/invoices/{$invoiceId}");
    $consultInvoiceResponse->assertStatus(200)
        ->assertJsonPath('data.invoice_number', 'FAC-E2E-2026-000101')
        ->assertJsonPath('data.billing_customer_name', 'Cliente Receptor SpA')
        ->assertJsonCount(2, 'data.items');

    // 7. Descargar el Stream binario de la Factura en PDF
    $pdfResponse = $this->get("http://{$this->domain}/api-tenant/billing/invoices/{$invoiceId}/pdf");
    $pdfResponse->assertStatus(200);
    expect($pdfResponse->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($pdfResponse->getContent())->toStartWith('%PDF-');

    // 8. Reenviar la Factura por correo electrónico
    $resendResponse = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices/{$invoiceId}/resend-email", [
        'email' => 'contabilidad@receptor.cl',
    ]);
    $resendResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');

    Mail::assertSent(InvoiceMail::class, function ($mail) {
        return $mail->hasTo('contabilidad@receptor.cl');
    });

    // 9. Consultar Métricas Agregadas
    $metricsResponse = $this->getJson("http://{$this->domain}/api-tenant/billing/metrics");
    $metricsResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.total_issued', 1)
        ->assertJsonPath('data.total_cancelled', 0);
    expect((float) $metricsResponse->json('data.total_billed'))->toEqual(1190.00);

    // 10. Filtrar Facturas
    $filterResponse = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices/filter", [
        'search' => 'Receptor',
        'status' => 'issued',
        'min_total' => 500,
    ]);
    $filterResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data.data');

    // 11. Anular la Factura con motivo
    $cancelResponse = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices/{$invoiceId}/cancel", [
        'reason' => 'Error en monto de descuento solicitado por cliente',
    ]);
    $cancelResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'cancelled')
        ->assertJsonPath('data.notes', 'Factura emitida directamente desde mostrador. | Anulada: Error en monto de descuento solicitado por cliente');

    // 12. Verificar que las métricas reflejan la anulación
    $metricsAfterCancel = $this->getJson("http://{$this->domain}/api-tenant/billing/metrics");
    $metricsAfterCancel->assertStatus(200)
        ->assertJsonPath('data.total_cancelled', 1);
});
