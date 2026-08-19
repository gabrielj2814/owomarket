<?php

declare(strict_types=1);

use App\Models\CentralCustomer;
use App\Models\CentralCustomerSsoToken;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as TenantCustomer;
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

    // Ensure central customers tables exist
    if (! Schema::hasTable('central_customers')) {
        $centralMigration = require base_path('database/migrations/2026_08_19_000001_create_central_customers_tables.php');
        $centralMigration->up();
    }

    // Ensure tenant customers tables exist
    if (! Schema::hasTable('customers')) {
        $customerMigration = require base_path('database/migrations/tenant/2025_10_28_144201_create_customers.php');
        $customerMigration->up();
    }
    if (! Schema::hasColumn('customers', 'central_uuid')) {
        $uuidMigration = require base_path('database/migrations/tenant/2026_08_19_000002_add_central_uuid_to_customers.php');
        $uuidMigration->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Test SSO',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);
});

test('POST /api/central/customer/register registers a new central customer with hashed password', function () {
    $email = 'carlos_' . bin2hex(random_bytes(3)) . '@example.com';

    $response = $this->postJson('/api/central/customer/register', [
        'name' => 'Carlos Mendoza',
        'email' => $email,
        'password' => 'secret12345',
        'phone' => '+584121234567',
        'document_id' => 'V-12345678',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'code' => 201,
        ]);

    $customer = CentralCustomer::where('email', $email)->first();
    expect($customer)->not->toBeNull();
    expect($customer->name)->toBe('Carlos Mendoza');
    expect(Hash::check('secret12345', $customer->password))->toBeTrue();
});

test('POST /api/central/customer/login authenticates valid central credentials', function () {
    $email = 'ana_' . bin2hex(random_bytes(3)) . '@example.com';
    CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Ana Gomez',
        'email' => $email,
        'password' => Hash::make('mypassword2026'),
        'phone' => '+584149876543',
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/central/customer/login', [
        'email' => $email,
        'password' => 'mypassword2026',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'code' => 200,
        ]);

    $wrongPassResponse = $this->postJson('/api/central/customer/login', [
        'email' => $email,
        'password' => 'wrongpass',
    ]);

    $wrongPassResponse->assertStatus(401);
});

test('POST /api/central/customer/sso/generate-token creates ephemeral token and POST /api-tenant/customer/sso/consume synchronizes customer in tenant', function () {
    $customerId = (string) Str::uuid();
    $email = 'pedro_' . bin2hex(random_bytes(3)) . '@example.com';

    $centralCustomer = CentralCustomer::create([
        'id' => $customerId,
        'name' => 'Pedro Pérez',
        'email' => $email,
        'password' => Hash::make('secretpass'),
        'phone' => '+584245556677',
        'is_active' => true,
    ]);

    // 1. Generate SSO Token on Central
    $tokenResponse = $this->postJson('/api/central/customer/sso/generate-token', [
        'customer_id' => $customerId,
        'target_domain' => $this->domain,
    ]);

    $tokenResponse->assertStatus(200);
    $token = $tokenResponse->json('data.token');
    expect($token)->not->toBeNull();

    // 2. Consume SSO Token on Tenant
    $consumeResponse = $this->postJson("http://{$this->domain}/api-tenant/customer/sso/consume", [
        'token' => $token,
    ]);

    $consumeResponse->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'code' => 200,
        ]);

    // Verify tenant database has synchronized record
    $tenantCustomer = TenantCustomer::where('central_uuid', $customerId)->first();
    expect($tenantCustomer)->not->toBeNull();
    expect($tenantCustomer->email)->toBe($email);
    expect($tenantCustomer->name)->toBe('Pedro Pérez');

    // 3. Second consume must fail because token is already used
    $reuseResponse = $this->postJson("http://{$this->domain}/api-tenant/customer/sso/consume", [
        'token' => $token,
    ]);

    $reuseResponse->assertStatus(410);
});

test('GET /api-tenant/customer/auth/session and POST /api-tenant/customer/auth/logout manage tenant customer session', function () {
    $customerId = (string) Str::uuid();
    $email = 'elena_' . bin2hex(random_bytes(3)) . '@example.com';

    $centralCustomer = CentralCustomer::create([
        'id' => $customerId,
        'name' => 'Elena Rivas',
        'email' => $email,
        'password' => Hash::make('secretpass'),
        'is_active' => true,
    ]);

    $ssoToken = CentralCustomerSsoToken::create([
        'id' => (string) Str::uuid(),
        'customer_id' => $customerId,
        'token' => (string) Str::random(64),
        'target_domain' => $this->domain,
        'expires_at' => now()->addMinutes(5),
    ]);

    // Log in via SSO
    $this->postJson("http://{$this->domain}/api-tenant/customer/sso/consume", [
        'token' => $ssoToken->token,
    ])->assertStatus(200);

    // Check session
    $sessionRes = $this->getJson("http://{$this->domain}/api-tenant/customer/auth/session");
    $sessionRes->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'authenticated' => true,
            ],
        ]);

    // Logout
    $logoutRes = $this->postJson("http://{$this->domain}/api-tenant/customer/auth/logout");
    $logoutRes->assertStatus(200);

    // Check session after logout
    $sessionAfterLogout = $this->getJson("http://{$this->domain}/api-tenant/customer/auth/session");
    $sessionAfterLogout->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'authenticated' => false,
            ],
        ]);
});
