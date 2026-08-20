<?php

declare(strict_types=1);

use App\Models\CentralProduct;
use App\Models\CommissionSettlement;
use App\Models\TenantOwnerSsoToken;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
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
    $consumeResponse->assertStatus(302)->assertRedirect('/dashboard');

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

    // 1. Get Wallet Summary
    $summaryResponse = $this->actingAs($user)->getJson("/tenant/owner/api/wallet-summary?user_id={$user->id}");
    $summaryResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data' => ['gross_sales', 'available_balance', 'bcv_rate']]);

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
