<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\Monetization\Application\Service\TenantAvailableBalance;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Fase 2 de `planes/por_hacer/PLAN_WALLET_Y_RETIROS.md`.
 *
 * `TenantAvailableBalance::netEarnings()` sumaba `order_total` y `commission_amount` de
 * TODAS las comisiones de la tienda, sin mirar ni el estado ni el canal. Y no es una
 * consulta de pantalla: es la que autoriza cuánto dinero real sale por
 * `CreateTenantOwnerPayoutRequestUseCase`.
 *
 * Así que una venta reembolsada, una cancelada, un cobro sin confirmar y una venta del
 * escaparate —donde el comerciante ya cobró directo en su banco— engordaban todas el saldo
 * retirable.
 */
beforeEach(function () {
    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Saldo',
        'slug' => 'saldo-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->balance = app(TenantAvailableBalance::class);
});

function comisionDe(Tenant $tenant, string $status, bool $central = true, float $total = 100.0, ?float $tasa = 50.0): PlatformCommission
{
    return PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'order_id' => (string) Str::uuid(),
        'central_order_id' => $central ? (string) Str::uuid() : null,
        'order_number' => 'ORD-'.strtoupper(Str::random(6)),
        'order_total' => $total,
        'commission_rate' => 8.00,
        'commission_amount' => round($total * 0.08, 2),
        'currency' => 'USD',
        'exchange_rate' => $tasa,
        'status' => $status,
        'payment_gateway' => 'pago_movil',
    ]);
}

it('una venta central cobrada es saldo retirable', function () {
    comisionDe($this->tenant, 'pending');

    expect($this->balance->requestable($this->tenant->id))->toBe(92.0);
});

it('una venta reembolsada no es saldo retirable', function () {
    comisionDe($this->tenant, 'refunded');

    expect($this->balance->requestable($this->tenant->id))->toBe(0.0);
});

it('una venta cancelada no es saldo retirable', function () {
    comisionDe($this->tenant, 'waived');

    expect($this->balance->requestable($this->tenant->id))->toBe(0.0);
});

it('un cobro que la plataforma no ha confirmado todavía no es retirable', function () {
    comisionDe($this->tenant, 'awaiting_payment');

    expect($this->balance->requestable($this->tenant->id))->toBe(0.0);
});

it('una venta del escaparate no entra en la wallet: ese dinero ya lo cobró la tienda', function () {
    // El comprador transfirió directo al banco del comerciante. La plataforma no recibió
    // nada, así que no le debe nada. Ofrecérselo para retirar era pagarle dos veces.
    comisionDe($this->tenant, 'pending', central: false);

    expect($this->balance->requestable($this->tenant->id))->toBe(0.0);
});

it('el retiro se rechaza cuando el saldo inflado no lo respalda', function () {
    // El test que importa: no comprueba una cifra en pantalla, comprueba que el dinero no
    // sale. Antes del arreglo estas cuatro comisiones sumaban 368 USD de saldo retirable y
    // esta solicitud pasaba.
    comisionDe($this->tenant, 'refunded');
    comisionDe($this->tenant, 'waived');
    comisionDe($this->tenant, 'awaiting_payment');
    comisionDe($this->tenant, 'pending', central: false);

    expect($this->balance->requestable($this->tenant->id))->toBe(0.0);

    $user = Src\Tenant\Infrastructure\Eloquent\Models\User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Dueño Tienda',
        'email' => 'duenno_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('password123'),
        'type' => 'tenant_owner',
        'is_active' => true,
    ]);
    $this->tenant->update(['user_id' => $user->id]);

    $solicitar = app(Src\Tenant\Application\UseCase\CreateTenantOwnerPayoutRequestUseCase::class);

    expect(fn () => $solicitar->execute($user->id, [
        'tenant_id' => $this->tenant->id,
        'amount' => 300.0,
        'payment_method' => 'Pago Móvil',
        'payment_details' => ['bank' => 'Banesco'],
    ]))->toThrow(Exception::class);
});

it('el desglose en bolívares usa la tasa congelada de cada venta', function () {
    comisionDe($this->tenant, 'pending', total: 200.0, tasa: 100.0);          // 184 USD → 18.400 Bs
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0);           //  92 USD →  4.600 Bs
    comisionDe($this->tenant, 'awaiting_payment', total: 100.0, tasa: 50.0);  // retenido: 4.600 Bs
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: null);           // sin valorar: 92 USD

    $desglose = $this->balance->breakdown($this->tenant->id);

    expect($desglose['disponible_bs'])->toBe(23000.0)
        ->and($desglose['retenido_bs'])->toBe(4600.0)
        // Sin tasa no se puede expresar en bolívares. Se muestra aparte en vez de excluirla
        // en silencio: al comerciante no puede desaparecerle dinero sin explicación.
        ->and($desglose['sin_valorar_usd'])->toBe(92.0)
        ->and($desglose['sin_valorar_count'])->toBe(1);
});
