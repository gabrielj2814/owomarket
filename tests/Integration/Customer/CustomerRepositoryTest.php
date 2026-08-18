<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Customer\Application\DTOs\FilterCustomersCriteria;
use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Domain\Entities\CustomerAddress;
use Src\Customer\Domain\ValueObjects\CustomerEmail;
use Src\Customer\Infrastructure\Eloquent\Repositories\EloquentCustomerRepository;
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

    if (! Schema::hasTable('customers')) {
        (require base_path('database/migrations/tenant/2025_10_28_144201_create_customers.php'))->up();
    }
    if (! Schema::hasTable('addresses')) {
        (require base_path('database/migrations/tenant/2025_10_28_144231_create_addresses.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Customer Test Store',
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
    $this->repository = new EloquentCustomerRepository;
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('saves and retrieves customer with addresses in tenant database', function () {
    $address1 = CustomerAddress::create(
        firstName: 'Gonzalo',
        lastName: 'Ramirez',
        addressLine1: 'Av. El Bosque 500',
        city: 'Las Condes',
        state: 'RM',
        postalCode: '7550000',
        country: 'Chile',
        type: 'shipping',
        isDefault: true
    );

    $customer = Customer::create(
        name: 'Gonzalo Ramirez',
        email: 'gonzalo.ramirez@empresa.cl',
        phone: '+56987654321',
        birthDate: '1988-03-25',
        gender: 'male',
        isActive: true,
        acceptsMarketing: true,
        metadata: ['channel' => 'pos'],
        addresses: [$address1]
    );

    $this->repository->save($customer);

    $found = $this->repository->findById($customer->id());

    expect($found)->not->toBeNull()
        ->and($found->name()->value())->toBe('Gonzalo Ramirez')
        ->and($found->email()->value())->toBe('gonzalo.ramirez@empresa.cl')
        ->and($found->phone()?->value())->toBe('+56987654321')
        ->and($found->birthDate()?->value())->toBe('1988-03-25')
        ->and($found->gender()?->value())->toBe('male')
        ->and($found->isActive())->toBeTrue()
        ->and($found->acceptsMarketing())->toBeTrue()
        ->and($found->addresses())->toHaveCount(1)
        ->and($found->addresses()[0]->addressLine1())->toBe('Av. El Bosque 500')
        ->and($found->addresses()[0]->isDefault())->toBeTrue();
});

it('finds customer by email', function () {
    $customer = Customer::create(
        name: 'Camila Diaz',
        email: 'camila@empresa.cl'
    );
    $this->repository->save($customer);

    $found = $this->repository->findByEmail(CustomerEmail::fromString('camila@empresa.cl'));

    expect($found)->not->toBeNull()
        ->and($found->id()->equals($customer->id()))->toBeTrue();
});

it('filters customers and retrieves paginated results and metrics', function () {
    $c1 = Customer::create(
        name: 'Ana Lopez',
        email: 'ana@test.cl',
        isActive: true,
        acceptsMarketing: true
    );
    $c2 = Customer::create(
        name: 'Bernardo O Higgins',
        email: 'bernardo@test.cl',
        isActive: false,
        acceptsMarketing: false
    );
    $c3 = Customer::create(
        name: 'Ana Maria Rossi',
        email: 'rossi@test.cl',
        isActive: true,
        acceptsMarketing: false
    );

    $this->repository->save($c1);
    $this->repository->save($c2);
    $this->repository->save($c3);

    // 1. Filtrar por búsqueda 'Ana'
    $filterRes = $this->repository->filter(new FilterCustomersCriteria(search: 'Ana'));
    expect($filterRes->total)->toBe(2)
        ->and($filterRes->items)->toHaveCount(2);

    // 2. Filtrar por activos
    $activeRes = $this->repository->filter(new FilterCustomersCriteria(is_active: true));
    expect($activeRes->total)->toBe(2);

    // 3. Consultar métricas
    $metrics = $this->repository->getMetrics();
    expect($metrics->total_customers)->toBe(3)
        ->and($metrics->active_customers)->toBe(2)
        ->and($metrics->marketing_subscribers)->toBe(1);

    // 4. Eliminar y verificar que no aparece
    $this->repository->delete($c1->id());
    $afterDelete = $this->repository->filter(new FilterCustomersCriteria);
    expect($afterDelete->total)->toBe(2);
});
