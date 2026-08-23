<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerSsoToken;
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
    $email = 'carlos_'.bin2hex(random_bytes(3)).'@example.com';

    $response = $this->postJson('/api/central/customer/register', [
        'name' => 'Carlos Mendoza',
        'email' => $email,
        'password' => 'Secret_12345',
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
    expect(Hash::check('Secret_12345', $customer->password))->toBeTrue();
});

test('POST /api/central/customer/login authenticates valid central credentials', function () {
    $email = 'ana_'.bin2hex(random_bytes(3)).'@example.com';
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
    $email = 'pedro_'.bin2hex(random_bytes(3)).'@example.com';

    $centralCustomer = CentralCustomer::create([
        'id' => $customerId,
        'name' => 'Pedro Pérez',
        'email' => $email,
        'password' => Hash::make('secretpass'),
        'phone' => '+584245556677',
        'is_active' => true,
    ]);

    // 1. Generate SSO Token on Central (requiere sesión propia del comprador
    // desde la Fase 0.3-D — antes bastaba mandar cualquier customer_id).
    $tokenResponse = $this->actingAs($centralCustomer, 'central_customer')
        ->postJson('/api/central/customer/sso/generate-token', [
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
    $email = 'elena_'.bin2hex(random_bytes(3)).'@example.com';

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

test('POST /api/central/customer/login establishes a real session usable by protected endpoints', function () {
    $email = 'session_'.bin2hex(random_bytes(3)).'@example.com';
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Sesión Real',
        'email' => $email,
        'password' => Hash::make('mypassword2026'),
        'is_active' => true,
    ]);

    // Antes del login, cualquier endpoint protegido del portal rechaza.
    $this->getJson("/api/central/customer/profile/{$customer->id}")->assertStatus(401);

    $loginResponse = $this->postJson('/api/central/customer/login', [
        'email' => $email,
        'password' => 'mypassword2026',
    ]);
    $loginResponse->assertStatus(200);

    // La sesión creada por el login basta: no hace falta mandar el ID en la URL de más.
    $this->getJson("/api/central/customer/profile/{$customer->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $customer->id);
});

test('Anonymous requests to the protected central customer API are rejected', function () {
    $this->getJson('/api/central/customer/orders')->assertStatus(401);
    $this->postJson('/api/central/customer/sso/generate-token')->assertStatus(401);
});

test('A customer cannot read or edit another customer profile', function () {
    $victim = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Víctima',
        'email' => 'victim_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => Hash::make('secretpass'),
        'is_active' => true,
    ]);

    $attacker = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Atacante',
        'email' => 'attacker_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => Hash::make('secretpass'),
        'is_active' => true,
    ]);

    $this->actingAs($attacker, 'central_customer')
        ->getJson("/api/central/customer/profile/{$victim->id}")
        ->assertStatus(403);

    $this->actingAs($attacker, 'central_customer')
        ->putJson("/api/central/customer/profile/{$victim->id}", ['name' => 'Hackeado'])
        ->assertStatus(403);

    expect($victim->fresh()->name)->toBe('Víctima');
});

/*
 * Hallazgo A1. Habia dos formas de iniciar sesion como cliente y la enlazada en el menu
 * movil era la rota: /auth/customer/login publicaba en /auth/login, el login de personal,
 * que busca en `users`. Un cliente vive en `central_customers`, asi que 401 con las
 * credenciales correctas. Y aunque acertara, la redireccion estaba comentada.
 *
 * La pagina se borro en vez de arreglarse: arreglarla la dejaba como una segunda
 * implementacion del modal, y dos caminos para lo mismo es el patron que produjo este bug
 * —y A2, y A3—. Queda el modal, que ademas resuelve el SSO desde el escaparate de una
 * tienda y no pierde el formulario a medio rellenar en el checkout.
 *
 * Este test es lo unico que impide que vuelva sin que nadie se entere.
 */
test('la pagina de login de cliente ya no existe (A1)', function () {
    $this->get('/auth/customer/login')->assertNotFound();
});

/*
 * Hallazgo A4. La regla nueva —8, mayuscula, minuscula, digito y simbolo— solo puede
 * aplicarse a contrasenas NUEVAS. Si tocara tambien al login, cada cliente dado de alta
 * cuando el servidor pedia min:6 se quedaria fuera de su propia cuenta, y el unico camino
 * de vuelta seria adivinar que la salida es "olvide mi contrasena".
 *
 * Por eso se quito la comprobacion de formato de los dos formularios de login. Este test
 * es lo que impide que alguien la reintroduzca "por coherencia".
 */
test('una contrasena antigua que ya no cumple la regla sigue sirviendo para entrar (A4)', function () {
    $email = 'antigua_'.bin2hex(random_bytes(3)).'@example.com';

    // Seis caracteres, sin mayuscula ni simbolo: lo que el servidor aceptaba con min:6.
    CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Cliente de siempre',
        'email' => $email,
        'password' => Hash::make('abc123'),
    ]);

    $this->postJson('/api/central/customer/login', [
        'email' => $email,
        'password' => 'abc123',
    ])->assertStatus(200);
});

test('una contrasena nueva debil se rechaza en el registro (A4)', function () {
    $this->postJson('/api/central/customer/register', [
        'name' => 'Cuenta debil',
        'email' => 'debil_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'abc123',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

/*
 * Hallazgo A4, tercer hermano. El cierre de A4 llevo Password::defaults() al registro y al
 * reset — los dos sitios que nombraba el hallazgo— y se salto el cambio de contrasena desde
 * el perfil, que es el tercer sitio donde nace una contrasena y se quedo en min:8.
 *
 * O sea que la politica se podia esquivar entera: registrarse cumpliendo la regla y luego
 * cambiar la contrasena a 'aaaaaaaa' desde Mi Perfil.
 */
test('cambiar la contrasena desde el perfil respeta la misma regla (A4)', function () {
    $email = 'perfil_'.bin2hex(random_bytes(3)).'@example.com';
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Cliente Perfil',
        'email' => $email,
        'password' => Hash::make('OwO_12345678'),
    ]);

    $this->actingAs($customer, 'central_customer')
        ->putJson("/api/central/customer/profile/{$customer->id}", [
            'name' => 'Cliente Perfil',
            'current_password' => 'OwO_12345678',
            'new_password' => 'aaaaaaaa',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('new_password');

    expect(Hash::check('OwO_12345678', $customer->fresh()->password))->toBeTrue();
});
