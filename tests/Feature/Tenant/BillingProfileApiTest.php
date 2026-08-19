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

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Billing API Store',
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

it('GET /api-tenant/billing/profile returns 200 and null data if not created yet', function () {
    $response = $this->getJson("http://{$this->domain}/api-tenant/billing/profile");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data', null);
});

it('PUT /api-tenant/billing/profile creates or updates billing profile successfully', function () {
    $payload = [
        'legal_name' => 'Comercializadora OwoMarket SpA',
        'tax_id' => '76999888-K',
        'billing_email' => 'facturacion@owomarket.cl',
        'phone' => '+56987654321',
        'address_line_1' => 'Av. Andrés Bello 2457, Of. 302',
        'address_line_2' => 'Torre Costanera',
        'city' => 'Providencia',
        'state' => 'Región Metropolitana',
        'postal_code' => '7510000',
        'country' => 'Chile',
        'invoice_prefix' => 'INV-',
        'next_invoice_number' => 10,
        'invoice_footer_notes' => 'Gracias por su compra. Documento exento de impuesto según resolución 123.',
    ];

    $response = $this->putJson("http://{$this->domain}/api-tenant/billing/profile", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.legal_name', 'Comercializadora OwoMarket SpA')
        ->assertJsonPath('data.tax_id', '76999888-K')
        ->assertJsonPath('data.invoice_prefix', 'INV-')
        ->assertJsonPath('data.next_invoice_number', 10);

    // Verify subsequent GET returns the persisted data
    $getResponse = $this->getJson("http://{$this->domain}/api-tenant/billing/profile");
    $getResponse->assertStatus(200)
        ->assertJsonPath('data.legal_name', 'Comercializadora OwoMarket SpA');
});

it('PUT /api-tenant/billing/profile returns 422 on validation failure', function () {
    $payload = [
        'legal_name' => '', // Required
        'tax_id' => '1', // Too short
    ];

    $response = $this->putJson("http://{$this->domain}/api-tenant/billing/profile", $payload);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonStructure(['errors']);
});
