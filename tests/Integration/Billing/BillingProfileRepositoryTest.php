<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Billing\Domain\Entities\BillingProfile;
use Src\Billing\Infrastructure\Eloquent\Repositories\EloquentBillingProfileRepository;
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
        'name' => 'Billing Test Store',
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
    $this->repository = new EloquentBillingProfileRepository;
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('saves and retrieves billing profile in tenant database', function () {
    expect($this->repository->getProfile())->toBeNull();

    $profile = BillingProfile::create(
        legalName: 'Empresa Demo SA',
        taxId: 'CL-99888777-1',
        billingEmail: 'billing@demo.cl',
        phone: '+5622334455',
        address: [
            'address_line_1' => 'Costanera 100',
            'city' => 'Santiago',
            'state' => 'RM',
            'postal_code' => '7500000',
            'country' => 'Chile',
        ],
        invoicePrefix: 'FAC-',
        nextInvoiceNumber: 50
    );

    $saved = $this->repository->save($profile);

    expect($saved->legalName())->toBe('Empresa Demo SA')
        ->and($saved->taxId()->value())->toBe('CL-99888777-1')
        ->and($saved->nextInvoiceNumber())->toBe(50);

    $retrieved = $this->repository->getProfile();
    expect($retrieved)->not->toBeNull()
        ->and($retrieved->legalName())->toBe('Empresa Demo SA')
        ->and($retrieved->address()->city())->toBe('Santiago');
});
