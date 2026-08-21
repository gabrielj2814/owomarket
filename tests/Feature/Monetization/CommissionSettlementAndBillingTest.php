<?php

declare(strict_types=1);

use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Monetization\Application\UseCases\CalculateAndRecordOrderCommissionUseCase;
use Src\Monetization\Application\UseCases\ConfirmAndSettleCommissionUseCase;
use Src\Monetization\Application\UseCases\GenerateTenantCommissionSettlementUseCase;
use Src\Monetization\Application\UseCases\GetSuperAdminMonetizationMetricsUseCase;
use Src\Monetization\Application\UseCases\ListSubscriptionPlansUseCase;
use Src\Monetization\Application\UseCases\SubscribeTenantToPlanUseCase;
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

    // Ensure central monetization and settlement tables exist
    if (! Schema::hasTable('subscription_plans')) {
        (require base_path('database/migrations/2026_08_19_000003_create_monetization_tables.php'))->up();
    }
    if (! Schema::hasTable('commission_settlements')) {
        (require base_path('database/migrations/2026_08_19_000005_create_commission_settlements_tables.php'))->up();
    }

    app(ListSubscriptionPlansUseCase::class)->execute();

    $tenantId = 't_settle_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Liquidaciones Test',
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

test('SuperAdmin can generate and confirm commission settlements converting pending commissions into collected', function () {
    $commissionUseCase = app(CalculateAndRecordOrderCommissionUseCase::class);
    $settleGenUseCase = app(GenerateTenantCommissionSettlementUseCase::class);
    $confirmUseCase = app(ConfirmAndSettleCommissionUseCase::class);

    // 1. Generate 3 pending commissions for this tenant ($100, $200, $300 at 8% default rate = $8, $16, $24)
    $comm1 = $commissionUseCase->execute(
        tenantId: $this->tenant->id,
        orderId: (string) Str::uuid(),
        orderNumber: 'ORD-S1',
        orderTotal: 100.00,
        paymentGateway: 'pago_movil'
    );

    $comm2 = $commissionUseCase->execute(
        tenantId: $this->tenant->id,
        orderId: (string) Str::uuid(),
        orderNumber: 'ORD-S2',
        orderTotal: 200.00,
        paymentGateway: 'pago_movil'
    );

    $comm3 = $commissionUseCase->execute(
        tenantId: $this->tenant->id,
        orderId: (string) Str::uuid(),
        orderNumber: 'ORD-S3',
        orderTotal: 300.00,
        paymentGateway: 'binance_pay'
    );

    expect($comm1->status)->toBe('pending');
    expect($comm2->status)->toBe('pending');
    expect($comm3->status)->toBe('pending');

    // 2. Generate Settlement via API
    $genResponse = $this->postJson('/api/central/monetization/settlements/generate', [
        'tenant_id' => $this->tenant->id,
        'type' => 'collection',
        'notes' => 'Liquidación mensual de comisiones Agosto 2026',
    ]);

    $genResponse->assertStatus(201)
        ->assertJsonPath('status', 'success');

    $settlementId = $genResponse->json('data.id');
    expect($settlementId)->not->toBeNull();

    $settlement = CommissionSettlement::with('commissions')->find($settlementId);
    expect($settlement)->not->toBeNull();
    expect($settlement->total_orders_count)->toBe(3);
    expect((float) $settlement->gross_sales_amount)->toBe(600.00); // 100+200+300
    expect((float) $settlement->commission_amount)->toBe(48.00); // 8+16+24
    expect($settlement->status)->toBe('pending');
    expect($settlement->commissions->count())->toBe(3);

    // Verify commissions now have settlement_id
    expect($comm1->fresh()->settlement_id)->toBe($settlementId);
    expect($comm2->fresh()->settlement_id)->toBe($settlementId);
    expect($comm3->fresh()->settlement_id)->toBe($settlementId);

    // 3. Tenant reports payment of this settlement
    $payReportResponse = $this->postJson("http://{$this->domain}/monetization/settlements/pay", [
        'settlement_id' => $settlementId,
        'payment_method' => 'pago_movil',
        'payment_reference' => 'PM-SETTLE-889900',
        'notes' => 'Comprobante transferido desde Banco Provincial',
    ]);

    $payReportResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');

    // 4. SuperAdmin confirms and settles the settlement
    $confirmResponse = $this->postJson("/api/central/monetization/settlements/{$settlementId}/confirm", [
        'payment_method' => 'pago_movil',
        'payment_reference' => 'PM-SETTLE-889900',
        'notes' => 'Pago validado en extracto bancario',
    ]);

    $confirmResponse->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'settled');

    // 5. Verify all linked commissions are now marked as collected
    expect($comm1->fresh()->status)->toBe('collected');
    expect($comm2->fresh()->status)->toBe('collected');
    expect($comm3->fresh()->status)->toBe('collected');

    // 6. Verify Tenant Settlement History endpoint
    $historyResponse = $this->getJson("http://{$this->domain}/monetization/settlements");
    $historyResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');
    expect(count($historyResponse->json('data')))->toBe(1);

    // 7. Verify SuperAdmin Metrics endpoint
    $metricsResponse = $this->getJson('/api/central/monetization/metrics');
    $metricsResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');
    expect((float) $metricsResponse->json('data.settlements.settled_amount'))->toBe(48.0);
});
