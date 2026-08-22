<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Monetization\Application\UseCases\CalculateAndRecordOrderCommissionUseCase;
use Src\Monetization\Application\UseCases\ConfirmAndSettleCommissionUseCase;
use Src\Monetization\Application\UseCases\GenerateTenantCommissionSettlementUseCase;
use Src\Monetization\Application\UseCases\ListSubscriptionPlansUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
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

    // Ensure central monetization and settlement tables exist
    if (! Schema::hasTable('subscription_plans')) {
        (require base_path('database/migrations/2026_08_19_000003_create_monetization_tables.php'))->up();
    }
    if (! Schema::hasTable('commission_settlements')) {
        (require base_path('database/migrations/2026_08_19_000005_create_commission_settlements_tables.php'))->up();
    }

    app(ListSubscriptionPlansUseCase::class)->execute();

    // Las rutas de /api/central/monetization/* exigen sesión de super administrador
    // (middleware 'auth' + 'super_admin'). Antes estaban abiertas al público, que era
    // precisamente el hallazgo A4 de la auditoría.
    $this->superAdmin = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin Monetización',
        'email' => 'monetizacion_'.bin2hex(random_bytes(3)).'@owomarket.local',
        'password' => bcrypt('Password123!'),
        'type' => 'super_admin',
        'is_active' => true,
    ]);

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
        paymentGateway: 'pago_movil',
        // Hallazgo N15: sólo entra en una liquidación lo que está cobrado. Estas tres
        // comisiones se crean como pagadas porque de eso trata el test.
        paid: true
    );

    $comm2 = $commissionUseCase->execute(
        tenantId: $this->tenant->id,
        orderId: (string) Str::uuid(),
        orderNumber: 'ORD-S2',
        orderTotal: 200.00,
        paymentGateway: 'pago_movil',
        paid: true
    );

    $comm3 = $commissionUseCase->execute(
        tenantId: $this->tenant->id,
        orderId: (string) Str::uuid(),
        orderNumber: 'ORD-S3',
        orderTotal: 300.00,
        paymentGateway: 'binance_pay',
        paid: true
    );

    expect($comm1->status)->toBe('pending');
    expect($comm2->status)->toBe('pending');
    expect($comm3->status)->toBe('pending');

    // 2. Generate Settlement via API (como super administrador)
    $genResponse = $this->actingAs($this->superAdmin)->postJson('/api/central/monetization/settlements/generate', [
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
    $confirmResponse = $this->actingAs($this->superAdmin)->postJson("/api/central/monetization/settlements/{$settlementId}/confirm", [
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
    $metricsResponse = $this->actingAs($this->superAdmin)->getJson('/api/central/monetization/metrics');
    $metricsResponse->assertStatus(200)
        ->assertJsonPath('status', 'success');
    expect((float) $metricsResponse->json('data.settlements.settled_amount'))->toBe(48.0);
});

test('los endpoints de monetización central rechazan a quien no es super administrador', function () {
    // Sin sesión: el middleware 'auth' responde 401 en peticiones JSON.
    $this->getJson('/api/central/monetization/metrics')
        ->assertStatus(401);

    $this->postJson('/api/central/monetization/custom-commission', [
        'tenant_id' => $this->tenant->id,
        'custom_commission_rate' => 0,
    ])->assertStatus(401);

    // Con sesión, pero de un usuario que no es super administrador: 403 del middleware 'super_admin'.
    $owner = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Dueño de Tienda',
        'email' => 'owner_'.bin2hex(random_bytes(3)).'@owomarket.local',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
        'is_active' => true,
    ]);

    $this->actingAs($owner)
        ->getJson('/api/central/monetization/metrics')
        ->assertStatus(403);

    $this->actingAs($owner)
        ->postJson('/api/central/monetization/custom-commission', [
            'tenant_id' => $this->tenant->id,
            'custom_commission_rate' => 0,
        ])->assertStatus(403);

    // La tasa de comisión de la tienda no debe haber cambiado.
    expect($this->tenant->fresh()->custom_commission_rate)->toBeNull();
});

/**
 * Hallazgo N15: la comisión nacía en `pending` sin mirar el `payment_status` —que para
 * pago móvil, transferencia manual y contra entrega es siempre `pending`— y la liquidación
 * se llevaba todo lo que estuviera en `pending`.
 *
 * Resultado: **a la tienda se le cobraba comisión por ventas que nunca cobró**. La Fase 1.2
 * tapó el síntoma revirtiendo al cancelar, pero eso dependía de que alguien cancelara: si
 * el cliente simplemente no pagaba y nadie tocaba el pedido, la comisión se liquidaba.
 */
test('una comisión sin pago confirmado NO entra en la liquidación (hallazgo N15)', function () {
    $commissionUseCase = app(CalculateAndRecordOrderCommissionUseCase::class);

    $sinPagar = $commissionUseCase->execute(
        tenantId: $this->tenant->id,
        orderId: (string) Str::uuid(),
        orderNumber: 'ORD-SINPAGAR',
        orderTotal: 500.00,
        paymentGateway: 'pago_movil',
        paid: false
    );

    expect($sinPagar->status)->toBe('awaiting_payment');

    // No hay nada cobrable, así que no se puede generar liquidación.
    expect(fn () => app(GenerateTenantCommissionSettlementUseCase::class)->execute($this->tenant->id))
        ->toThrow(Exception::class);
});

test('confirmar el pago vuelve cobrable la comisión (hallazgo N15)', function () {
    $orderId = (string) Str::uuid();

    app(CalculateAndRecordOrderCommissionUseCase::class)->execute(
        tenantId: $this->tenant->id,
        orderId: $orderId,
        orderNumber: 'ORD-PAGADO',
        orderTotal: 500.00,
        paymentGateway: 'pago_movil',
        paid: false
    );

    app(Src\Monetization\Application\UseCases\ActivateOrderCommissionUseCase::class)->execute($orderId);

    expect(PlatformCommission::where('order_id', $orderId)->value('status'))->toBe('pending');
});

/**
 * Hallazgo N16: revertir una comisión **ya liquidada** sólo dejaba una marca
 * `requires_manual_adjustment` y un aviso en el log. Alguien tenía que acordarse de
 * descontársela al comerciante a mano.
 */
test('revertir una comisión ya liquidada emite una nota de crédito (hallazgo N16)', function () {
    $orderId = (string) Str::uuid();

    $comision = app(CalculateAndRecordOrderCommissionUseCase::class)->execute(
        tenantId: $this->tenant->id,
        orderId: $orderId,
        orderNumber: 'ORD-LIQUIDADO',
        orderTotal: 1000.00,
        paymentGateway: 'pago_movil',
        paid: true
    );

    // Se liquida y se cobra.
    $settlement = app(GenerateTenantCommissionSettlementUseCase::class)->execute($this->tenant->id);
    expect(PlatformCommission::find($comision->id)->settlement_id)->toBe($settlement->id);

    // Y después el pedido se reembolsa.
    app(Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase::class)->execute(
        $orderId,
        Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase::REASON_REFUNDED,
        'Devolución del cliente'
    );

    $notaCredito = PlatformCommission::where('order_id', $orderId)
        ->where('id', '!=', $comision->id)
        ->first();

    expect($notaCredito)->not->toBeNull()
        ->and($notaCredito->metadata['credit_note'])->toBeTrue()
        ->and((float) $notaCredito->commission_amount)->toBe(-1 * (float) $comision->commission_amount)
        ->and($notaCredito->status)->toBe('pending')
        ->and($notaCredito->settlement_id)->toBeNull();

    // La siguiente liquidación la recoge sola y el neto sale corregido: no hace falta
    // que nadie se acuerde de un ajuste manual.
    $siguiente = app(GenerateTenantCommissionSettlementUseCase::class)->execute($this->tenant->id);
    expect((float) $siguiente->commission_amount)->toBe(-1 * (float) $comision->commission_amount);
});
