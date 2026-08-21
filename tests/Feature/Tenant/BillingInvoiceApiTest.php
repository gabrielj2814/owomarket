<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        'name' => 'Billing Invoice API Store',
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

it('POST /api-tenant/billing/invoices creates a new direct invoice and returns 201', function () {
    $payload = [
        'customer_name' => 'Pedro Pascal',
        'customer_email' => 'pedro@mandalorian.com',
        'customer_tax_id' => '11223344-5',
        'customer_address_line_1' => 'Av Siempre Viva 742',
        'customer_city' => 'Santiago',
        'customer_state' => 'RM',
        'customer_postal_code' => '8320000',
        'customer_country' => 'Chile',
        'items' => [
            [
                'description' => 'Casco Beskar',
                'quantity' => 1,
                'unit_price' => 1000.00,
                'tax_rate' => 19.0,
                'discount_amount' => 50.00,
            ],
            [
                'description' => 'Jetpack',
                'quantity' => 1,
                'unit_price' => 500.00,
                'tax_rate' => 19.0,
                'discount_amount' => 0.0,
            ],
        ],
        'payment_method' => 'manual',
        'payment_status' => 'paid',
        'status' => 'issued',
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices", $payload);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.billing_customer_name', 'Pedro Pascal')
        ->assertJsonPath('data.subtotal', 1500)
        ->assertJsonPath('data.discount_amount', 50)
        ->assertJsonPath('data.total', 1725.5); // (1000 - 50 = 950 * 1.19 = 1130.5) + (500 * 1.19 = 595) = 1725.5

    $invoiceId = $response->json('data.id');

    // Consult invoice
    $getResponse = $this->getJson("http://{$this->domain}/api-tenant/billing/invoices/{$invoiceId}");
    $getResponse->assertStatus(200)
        ->assertJsonPath('data.id', $invoiceId)
        ->assertJsonCount(2, 'data.items');
});

it('POST /api-tenant/billing/invoices/filter returns paginated invoices', function () {
    // Create an invoice first
    $this->postJson("http://{$this->domain}/api-tenant/billing/invoices", [
        'customer_name' => 'Ana Gomez',
        'customer_email' => 'ana@test.com',
        'customer_address_line_1' => 'Calle Central 1',
        'customer_city' => 'Santiago',
        'customer_state' => 'RM',
        'customer_postal_code' => '8320000',
        'customer_country' => 'Chile',
        'items' => [
            ['description' => 'Servicio X', 'quantity' => 1, 'unit_price' => 100.00],
        ],
    ])->assertStatus(201);

    $filterResponse = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices/filter", [
        'search' => 'Ana',
        'page' => 1,
        'per_page' => 10,
    ]);

    $filterResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.pagination.total', 1)
        ->assertJsonPath('data.data.0.billing_customer_name', 'Ana Gomez');
});

it('POST /api-tenant/billing/invoices/{id}/cancel cancels an invoice successfully', function () {
    $createResponse = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices", [
        'customer_name' => 'Carlos Ruiz',
        'customer_email' => 'carlos@test.com',
        'customer_address_line_1' => 'Calle 5',
        'customer_city' => 'Santiago',
        'customer_state' => 'RM',
        'customer_postal_code' => '8320000',
        'customer_country' => 'Chile',
        'items' => [
            ['description' => 'Producto Z', 'quantity' => 1, 'unit_price' => 200.00],
        ],
    ]);

    $invoiceId = $createResponse->json('data.id');

    $cancelResponse = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices/{$invoiceId}/cancel", [
        'reason' => 'Pedido duplicado por error',
    ]);

    $cancelResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'cancelled');
});

it('GET /api-tenant/billing/metrics returns billing KPI metrics', function () {
    $response = $this->getJson("http://{$this->domain}/api-tenant/billing/metrics");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'data' => ['total_billed', 'total_issued', 'total_paid', 'total_cancelled'],
        ]);
});

test('Los números de factura son correlativos y no se repiten (hallazgo C4)', function () {
    // Antes, `getProfile()` leía el contador sin bloqueo, el incremento ocurría
    // en memoria y se persistía aparte: dos emisiones con next_invoice_number
    // = 42 generaban ambas FAC-2026-000042.
    $numeros = [];

    foreach (range(1, 5) as $i) {
        $response = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices", [
            'customer_name' => "Cliente {$i}",
            'customer_tax_id' => 'J-1234567-'.$i,
            'customer_email' => "cliente{$i}@example.com",
            'customer_address_line_1' => 'Av. Principal 100',
            'customer_city' => 'Caracas',
            'customer_state' => 'Miranda',
            'customer_postal_code' => '1050',
            'customer_country' => 'Venezuela',
            'items' => [
                [
                    'description' => 'Servicio de prueba',
                    'quantity' => 1,
                    'unit_price' => 100.00,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $numeros[] = $response->json('data.invoice_number');
    }

    // Ni uno repetido.
    expect(count(array_unique($numeros)))->toBe(5);

    // Y son consecutivos: el correlativo avanza de uno en uno.
    $correlativos = array_map(
        fn (string $n) => (int) substr($n, strrpos($n, '-') + 1),
        $numeros
    );
    expect($correlativos)->toBe([
        $correlativos[0],
        $correlativos[0] + 1,
        $correlativos[0] + 2,
        $correlativos[0] + 3,
        $correlativos[0] + 4,
    ]);
});
