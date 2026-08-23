<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Admin\Application\UseCase\ApproveCentralPayoutRequestUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/*
|--------------------------------------------------------------------------
| Hallazgo T1 - el saldo se comprobaba al PEDIR el retiro y nunca mas
|--------------------------------------------------------------------------
|
| CreateTenantOwnerPayoutRequestUseCase comprueba que el importe no supere el saldo
| disponible, y lo hace bien: descuenta los retiros ya liquidados Y los pendientes.
|
| Pero ApproveCentralPayoutRequestUseCase - el paso donde el dinero sale de verdad - solo
| comprobaba que la solicitud existiera, estuviera 'pending' y trajera referencia bancaria.
| El saldo no se volvia a mirar.
|
| Es el hallazgo C3 otra vez (carrera al generar liquidaciones) y B3/C6 (N peticiones
| paralelas pasando la misma comprobacion previa). La leccion llego a las liquidaciones y a
| los cupones, y se salto los retiros - que es el unico de los tres donde sale dinero.
*/
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($b) => $b !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('subscription_plans')) {
        (require base_path('database/migrations/2026_08_19_000003_create_monetization_tables.php'))->up();
    }
    if (! Schema::hasTable('commission_settlements')) {
        (require base_path('database/migrations/2026_08_19_000005_create_commission_settlements_tables.php'))->up();
    }

    $tenantId = 't_pay_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Retiros',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);

    // Ventas por 500 con 40 de comision: 460 de ganancias netas.
    PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-PAY-1',
        'order_total' => 500.00,
        'commission_rate' => 8.00,
        'commission_amount' => 40.00,
        'currency' => 'USD',
        'status' => 'pending',
    ]);
});

test('no se puede aprobar un retiro que supera el saldo disponible actual (T1)', function () {
    // El comerciante pide retirar sus 460 disponibles. Legitimo en ese momento.
    $solicitud = CommissionSettlement::create([
        'id' => (string) Str::uuid(),
        'settlement_number' => 'PAY-TEST-001',
        'tenant_id' => $this->tenant->id,
        'type' => 'payout',
        'gross_sales_amount' => 460.00,
        'commission_amount' => 0.00,
        'net_amount' => 460.00,
        'currency' => 'USD',
        'status' => 'pending',
        'payment_method' => 'pago_movil',
    ]);

    // Despues de pedirlo, el saldo baja: un ajuste de comision de 400 (una devolucion, una
    // correccion, una penalizacion). Las ganancias netas pasan de 460 a 60, asi que el
    // retiro pendiente de 460 ya no tiene respaldo.
    PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-AJUSTE',
        'order_total' => 0.00,
        'commission_rate' => 0.00,
        'commission_amount' => 400.00,
        'currency' => 'USD',
        'status' => 'pending',
    ]);

    $aprobar = app(ApproveCentralPayoutRequestUseCase::class);

    // El dinero sale aqui. Si esto pasa, la plataforma paga 460 sobre un saldo de 60.
    expect(fn () => $aprobar->execute($solicitud->id, (string) Str::uuid(), [
        'payment_reference' => 'REF-123456',
    ]))->toThrow(Exception::class);

    expect($solicitud->fresh()->status)->toBe('pending');
});
