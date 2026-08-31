<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Tenant\Infrastructure\Eloquent\Models\Domain;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
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

    if (! Schema::hasTable('tenants')) {
        (require base_path('database/migrations/2019_09_15_000010_create_tenants_table.php'))->up();
    }

    if (! Schema::hasTable('domains')) {
        (require base_path('database/migrations/2019_09_15_000020_create_domains_table.php'))->up();
    }

    if (! Schema::hasTable('tenant_owner_sso_tokens')) {
        (require base_path('database/migrations/2026_08_19_000012_create_tenant_owner_sso_tokens_table.php'))->up();
    }

    if (! Schema::hasTable('central_products')) {
        (require base_path('database/migrations/2026_08_19_000007_create_central_products_table.php'))->up();
    }

    if (! Schema::hasTable('commission_settlements')) {
        (require base_path('database/migrations/2026_08_19_000005_create_commission_settlements_tables.php'))->up();
    }

    if (! Schema::hasTable('platform_commissions')) {
        (require base_path('database/migrations/2026_08_19_000003_create_monetization_tables.php'))->up();
    }
});

test('Tenant Owner can generate and consume SSO token to login directly into store backoffice', function () {
    $user = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Owner SSO Tester',
        'email' => 'owner_sso_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);

    $tenant = Tenant::create([
        'id' => 'store-sso-test',
        'name' => 'Store SSO Test',
        'slug' => 'store-sso-test',
        'status' => 'active',
        'request' => 'approved',
    ]);

    Domain::create([
        'id' => (string) Str::uuid(),
        'domain' => 'store-sso-test.owomarket.local',
        'tenant_id' => 'store-sso-test',
    ]);

    $tenant->users()->attach($user->id, [
        'id' => (string) Str::uuid(),
        'role' => 'owner',
    ]);

    // 1. Generate SSO Token
    $response = $this->actingAs($user)->postJson('/tenant/owner/api/sso-token', [
        'user_id' => $user->id,
        'tenant_id' => 'store-sso-test',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data' => ['token', 'redirect_url', 'expires_at']]);

    $token = $response->json('data.token');

    // 2. Consume SSO Token
    $consumeResponse = $this->get("/auth/sso-consume?token={$token}");
    $consumeResponse->assertStatus(302)->assertRedirect("/tenant/backoffice/{$user->id}/dashboard");

    $this->assertAuthenticatedAs($user);
});

test('Tenant Owner can view wallet summary and request payout', function () {
    $user = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Wallet Tester',
        'email' => 'wallet_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);

    $tenant = Tenant::create([
        'id' => 'wallet-store-test',
        'name' => 'Wallet Store',
        'slug' => 'wallet-store',
        'status' => 'active',
        'request' => 'approved',
    ]);

    $tenant->users()->attach($user->id, [
        'id' => (string) Str::uuid(),
        'role' => 'owner',
    ]);

    // Ventas previas que respaldan el saldo disponible para el retiro.
    PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => 'wallet-store-test',
        'order_id' => (string) Str::uuid(),
        // Fase 2: venta del canal central, que es la unica que genera saldo en la wallet.
        'central_order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-TEST-001',
        'order_total' => 500.00,
        'commission_rate' => 8.00,
        'commission_amount' => 40.00,
        'currency' => 'USD',
        'exchange_rate' => 50.00,
        'status' => 'collected',
        // Fase 4b: entregada, luego retirable. Sin esto el saldo la retiene.
        'released_at' => now(),
    ]);

    // 1. Get Wallet Summary
    $summaryResponse = $this->actingAs($user)->getJson("/tenant/owner/api/wallet-summary?user_id={$user->id}");
    $summaryResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        // `bcv_rate` ya no existe: era una tasa escrita a mano en el caso de uso. Y
        // `available_balance` es ahora BOLIVARES, que es lo que el comerciante retira: el
        // dolar solo es la unidad en la que se puso el precio (Fases 1 y 3).
        ->assertJsonStructure(['data' => ['gross_sales', 'available_balance', 'tenant_id']])
        ->assertJsonPath('data.available_balance', 23000);   // (500 - 40) * 50

    // 2. Request Payout
    $payoutResponse = $this->actingAs($user)->postJson('/tenant/owner/api/payout-request', [
        'user_id' => $user->id,
        'tenant_id' => 'wallet-store-test',
        'amount' => 150.00,
        'payment_method' => 'Pago Móvil (Bs. BCV)',
        'payment_details' => [
            'bank' => 'Banesco (0134)',
            'phone' => '04121234567',
            'document_id' => 'V-24890123',
        ],
        'notes' => 'Liquidación semanal',
    ]);

    $payoutResponse->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.amount', 150)
        ->assertJsonPath('data.status', 'pending');
});

test('Tenant Owner can list products and toggle publication in central marketplace', function () {
    $user = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Catalog Tester',
        'email' => 'catalog_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);

    $tenant = Tenant::create([
        'id' => 'catalog-store-test',
        'name' => 'Catalog Store',
        'slug' => 'catalog-store',
        'status' => 'active',
        'request' => 'approved',
    ]);

    $tenant->users()->attach($user->id, [
        'id' => (string) Str::uuid(),
        'role' => 'owner',
    ]);

    $product = CentralProduct::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => 'catalog-store-test',
        'tenant_product_id' => 'prod-test-99',
        'name' => 'Teclado Mecánico RGB Pro',
        'slug' => 'teclado-mecanico-rgb-pro-'.bin2hex(random_bytes(2)),
        'price' => 45.00,
        'stock' => 12,
        'is_visible' => true,
    ]);

    // 1. List products
    $listResponse = $this->actingAs($user)->getJson("/tenant/owner/api/products?user_id={$user->id}");
    $listResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.metrics.total_products', 1);

    // 2. Toggle publication to false
    $toggleResponse = $this->actingAs($user)->postJson("/tenant/owner/api/products/{$product->id}/toggle-marketplace", [
        'user_id' => $user->id,
        'status' => false,
    ]);

    $toggleResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.is_visible', false);

    expect(CentralProduct::find($product->id)->is_visible)->toBeFalse();
});

test('Tenant Owner central web views render with 200 status', function () {
    $user = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Views Tester',
        'email' => 'views_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);

    $this->actingAs($user)->get("/tenant/owner/backoffice/{$user->id}/dashboard")->assertStatus(200);
    $this->actingAs($user)->get("/tenant/owner/backoffice/{$user->id}/wallet")->assertStatus(200);
    $this->actingAs($user)->get("/tenant/owner/backoffice/{$user->id}/catalog")->assertStatus(200);
    $this->actingAs($user)->get("/tenant/owner/backoffice/{$user->id}/billing")->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| AUDITORIA 22/08 — el uuid de la URL decide de quien son los datos
|--------------------------------------------------------------------------
|
| `/tenant/owner/backoffice/{user_uuid}/wallet` y sus hermanas llevan solo `auth`. El
| controlador toma el `{user_uuid}` de la URL y se lo pasa al caso de uso sin compararlo
| nunca con la sesion, asi que cualquiera con sesion central puede leer los datos de
| cualquier otro propietario cambiando un uuid en la barra de direcciones.
|
| Estos tests se escriben ROJOS a proposito: documentan el agujero antes de taparlo.
*/

/** Crea un propietario con su tienda. */
function propietarioConTienda(string $slug, string $nombre): array
{
    $user = User::create([
        'id' => (string) Str::uuid(),
        'name' => $nombre,
        'email' => strtolower($slug).'_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);

    $tenant = Tenant::create([
        'id' => $slug,
        'name' => $nombre,
        'slug' => $slug,
        'status' => 'active',
        'request' => 'approved',
    ]);

    Domain::create([
        'id' => (string) Str::uuid(),
        'domain' => $slug.'.owomarket.local',
        'tenant_id' => $slug,
    ]);

    $tenant->users()->attach($user->id, [
        'id' => (string) Str::uuid(),
        'role' => 'owner',
    ]);

    return ['user' => $user, 'tenant' => $tenant];
}

test('un propietario no puede leer la billetera de otro', function () {
    $ana = propietarioConTienda('tienda-ana', 'Ana');
    $beto = propietarioConTienda('tienda-beto', 'Beto');

    // La billetera de Beto tiene dinero: ventas, comisiones y una liquidacion pagada con
    // su referencia de pago.
    PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $beto['tenant']->id,
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-BETO-1',
        'order_total' => 1000.00,
        'commission_rate' => 10.0,
        'commission_amount' => 100.00,
        'net_amount' => 900.00,
        'currency' => 'USD',
        'status' => 'pending',
    ]);

    // Ana inicia sesion y pide la billetera de Beto cambiando el uuid de la URL.
    $respuesta = $this->actingAs($ana['user'])
        ->get('/tenant/owner/backoffice/'.$beto['user']->id.'/wallet');

    // Lo correcto es no dejarle ver nada de Beto.
    // Comprobado el 22/08: hoy devuelve 200 y el payload trae las ventas brutas, las
    // comisiones y el saldo disponible de Beto.
    expect($respuesta->status())->toBeIn([403, 404]);
});

test('un propietario no puede leer la facturacion de otro', function () {
    $ana = propietarioConTienda('tienda-ana-fact', 'Ana');
    $beto = propietarioConTienda('tienda-beto-fact', 'Beto');

    $respuesta = $this->actingAs($ana['user'])
        ->get('/tenant/owner/backoffice/'.$beto['user']->id.'/billing');

    expect($respuesta->status())->toBeIn([403, 404]);
});

test('un propietario no puede leer el panel de otro', function () {
    $ana = propietarioConTienda('tienda-ana-dash', 'Ana');
    $beto = propietarioConTienda('tienda-beto-dash', 'Beto');

    $respuesta = $this->actingAs($ana['user'])
        ->get('/tenant/owner/backoffice/'.$beto['user']->id.'/dashboard');

    expect($respuesta->status())->toBeIn([403, 404]);
});

/*
|--------------------------------------------------------------------------
| AUDITORIA 22/08 — P0: emitir un token SSO para CUALQUIER tienda
|--------------------------------------------------------------------------
|
| `POST /tenant/admin/api/tenants/{id}/sso-token` lleva solo `auth`. Su gemela protegida,
| `POST /admin/api/tenants/{id}/sso-token`, exige `super_admin` — lo que demuestra que la
| intencion era protegerla. El duplicado la esquiva.
|
| El token se consume con `Auth::login($user, true)`, o sea que concede sesion real en el
| backoffice de esa tienda.
*/

test('un propietario no puede emitir un token SSO para la tienda de otro', function () {
    $ana = propietarioConTienda('tienda-ana-sso', 'Ana');
    $beto = propietarioConTienda('tienda-beto-sso', 'Beto');

    // Ana tiene sesion central legitima — es su cuenta — y pide entrar a la tienda de Beto.
    $respuesta = $this->actingAs($ana['user'])
        ->postJson('/tenant/admin/api/tenants/'.$beto['tenant']->id.'/sso-token');

    // Comprobado el 22/08: hoy devuelve 200 con `sso_url` listo para abrir, y consumirlo
    // hace `Auth::login()` como el usuario de la tienda de Beto.
    expect($respuesta->status())->toBeIn([403, 404]);
});

test('la ruta protegida de SSO si exige super_admin', function () {
    // El contraste: la misma accion bajo /admin si esta cerrada. Confirma que el problema
    // es el duplicado, no que falte la intencion de protegerla.
    $ana = propietarioConTienda('tienda-ana-sso2', 'Ana');
    $beto = propietarioConTienda('tienda-beto-sso2', 'Beto');

    $respuesta = $this->actingAs($ana['user'])
        ->postJson('/admin/api/tenants/'.$beto['tenant']->id.'/sso-token');

    expect($respuesta->status())->toBeIn([401, 403]);
});

test('un propietario no puede suspender la tienda de otro', function () {
    $ana = propietarioConTienda('tienda-ana-susp', 'Ana');
    $beto = propietarioConTienda('tienda-beto-susp', 'Beto');

    $respuesta = $this->actingAs($ana['user'])
        ->patchJson('/tenant/backoffice/'.$beto['tenant']->id.'/suspended');

    expect($respuesta->status())->toBeIn([403, 404]);

    // Y sobre todo: que la tienda de Beto siga en pie.
    expect(Tenant::find($beto['tenant']->id)->status)->toBe('active');
});
