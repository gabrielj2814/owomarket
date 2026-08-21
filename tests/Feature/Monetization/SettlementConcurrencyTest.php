<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Monetization\Application\UseCases\GenerateTenantCommissionSettlementUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Fase 1.3 — hallazgo C3: carrera al generar liquidaciones.
 *
 * Antes se leían las comisiones pendientes, se creaba la liquidación con los
 * totales y DESPUÉS se enlazaban con un `update` que no revalidaba
 * `whereNull('settlement_id')`. Sin transacción y sin `lockForUpdate`.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('subscription_plans')) {
        (require base_path('database/migrations/2026_08_19_000003_create_monetization_tables.php'))->up();
    }
    if (! Schema::hasTable('commission_settlements')) {
        (require base_path('database/migrations/2026_08_19_000005_create_commission_settlements_tables.php'))->up();
    }

    $tenantId = 't_set_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Liquidaciones',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);

    // Tres comisiones pendientes que suman $500 de ventas y $40 de comisión.
    foreach ([200.00, 200.00, 100.00] as $i => $orderTotal) {
        PlatformCommission::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'order_id' => (string) Str::uuid(),
            'order_number' => 'ORD-SET-'.$i,
            'order_total' => $orderTotal,
            'commission_rate' => 8.00,
            'commission_amount' => round($orderTotal * 0.08, 2),
            'currency' => 'USD',
            'status' => 'pending',
        ]);
    }
});

test('Generar la liquidación dos veces no duplica los importes (hallazgo C3)', function () {
    $useCase = app(GenerateTenantCommissionSettlementUseCase::class);

    $primera = $useCase->execute($this->tenant->id, 'collection');

    expect((float) $primera->gross_sales_amount)->toBe(500.00);
    expect((float) $primera->commission_amount)->toBe(40.00);
    expect($primera->total_orders_count)->toBe(3);

    // El doble clic del escenario de la auditoría: la segunda generación ya no
    // encuentra comisiones libres y falla limpiamente, en vez de crear una
    // segunda liquidación por los mismos $500 y robarle las comisiones a la
    // primera.
    expect(fn () => $useCase->execute($this->tenant->id, 'collection'))
        ->toThrow(Exception::class);

    expect(CommissionSettlement::where('tenant_id', $this->tenant->id)->count())->toBe(1);

    // Y las tres comisiones siguen apuntando a la liquidación original.
    expect(PlatformCommission::where('settlement_id', $primera->id)->count())->toBe(3);
});

test('Las comisiones anuladas no entran en la liquidación (hallazgos C3 + D2)', function () {
    // Se anula una de las tres, como haría una cancelación de pedido.
    $anulada = PlatformCommission::where('tenant_id', $this->tenant->id)->first();
    $anulada->update(['status' => 'waived']);

    $settlement = app(GenerateTenantCommissionSettlementUseCase::class)
        ->execute($this->tenant->id, 'collection');

    // Sólo las dos vivas: $300 de ventas y $24 de comisión.
    expect($settlement->total_orders_count)->toBe(2);
    expect((float) $settlement->gross_sales_amount)->toBe(300.00);
    expect((float) $settlement->commission_amount)->toBe(24.00);

    $anulada->refresh();
    expect($anulada->settlement_id)->toBeNull();
});
