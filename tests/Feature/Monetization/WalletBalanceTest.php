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

function comisionDe(Tenant $tenant, string $status, bool $central = true, float $total = 100.0, ?float $tasa = 50.0, bool $entregado = true): PlatformCommission
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
        // Fase 4b: liberada por defecto para que los tests de las fases anteriores sigan
        // hablando de lo que hablaban. Los de la retencion la pasan a `false` a proposito.
        // Entregada hace tiempo por defecto: los tests de las fases anteriores hablan del
        // saldo, no de la ventana de garantia. Los de la ventana ponen su propia fecha.
        'released_at' => $entregado ? now()->subDays(30) : null,
        'payment_gateway' => 'pago_movil',
    ]);
}

it('una venta central cobrada es saldo retirable', function () {
    comisionDe($this->tenant, 'pending');

    // 92 USD a la tasa de 50 con la que compró el cliente. En bolívares desde la Fase 3.
    expect($this->balance->requestable($this->tenant->id))->toBe(4600.0);
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

    // Este test estaba verde por el motivo equivocado: asignaba la propiedad con
    // `tenants.user_id`, que no es como se resuelve, asi que la solicitud fallaba con un 403
    // de permisos y nunca llegaba a mirar el saldo. Ahora el usuario ES el dueño, y el
    // rechazo tiene que venir del importe.
    $user = duenoDe($this->tenant);

    $solicitar = app(Src\Tenant\Application\UseCase\CreateTenantOwnerPayoutRequestUseCase::class);

    try {
        $solicitar->execute($user->id, [
            'tenant_id' => $this->tenant->id,
            'amount' => 300.0,
            'payment_method' => 'Pago Móvil',
            'payment_details' => ['bank' => 'Banesco'],
        ]);
        $this->fail('El retiro deberia haberse rechazado por falta de saldo.');
    } catch (Exception $e) {
        expect($e->getCode())->toBe(422)
            ->and($e->getMessage())->toContain('supera tu saldo disponible');
    }
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

/**
 * Fase 3: el saldo y el retiro pasan a bolívares.
 *
 * El dólar es la unidad en la que se pone el precio: al comprador se le muestra el precio en
 * dólares y su equivalente en bolívares a la tasa del día, y paga bolívares. Nunca entra un
 * dólar a ninguna cuenta.
 *
 * Antes el saldo se calculaba en dólares mientras la pantalla enseñaba bolívares, así que el
 * comerciante veía una unidad y escribía en otra en el mismo formulario.
 */
function duenoDe(Tenant $tenant): Src\Tenant\Infrastructure\Eloquent\Models\User
{
    $user = Src\Tenant\Infrastructure\Eloquent\Models\User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Dueño Tienda',
        'email' => 'duenno_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('password123'),
        'type' => 'tenant_owner',
        'is_active' => true,
    ]);

    // La propiedad se resuelve por `tenant_users`, no por una columna en `tenants`.
    $tenant->users()->attach($user->id, ['id' => (string) Str::uuid(), 'role' => 'owner']);

    return $user;
}

it('el saldo retirable son los bolívares que aportó cada venta', function () {
    comisionDe($this->tenant, 'pending', total: 200.0, tasa: 100.0);   // 184 USD → 18.400 Bs
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0);    //  92 USD →  4.600 Bs

    // 23.000, no 276. Si esto devolviera dólares, el comerciante pediría 276 contra un saldo
    // que la pantalla le muestra como 23.000.
    expect($this->balance->requestable($this->tenant->id))->toBe(23000.0);
});

it('el retiro se guarda en bolívares y descuenta del saldo', function () {
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0);   // 4.600 Bs
    $user = duenoDe($this->tenant);

    $retiro = app(Src\Tenant\Application\UseCase\CreateTenantOwnerPayoutRequestUseCase::class)
        ->execute($user->id, [
            'tenant_id' => $this->tenant->id,
            'amount' => 4000.0,
            'payment_method' => 'Pago Móvil',
            'payment_details' => ['bank' => 'Banesco'],
        ]);

    expect($retiro->currency)->toBe('VES')
        ->and((float) $retiro->net_amount)->toBe(4000.0);

    // Y el saldo baja: pedir dos veces seguidas el total no cuela.
    expect($this->balance->requestable($this->tenant->id))->toBe(600.0);
});

it('rechaza un retiro por más bolívares de los que hay', function () {
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0);   // 4.600 Bs
    $user = duenoDe($this->tenant);

    $solicitar = app(Src\Tenant\Application\UseCase\CreateTenantOwnerPayoutRequestUseCase::class);

    // 5.000 Bs no caben en 4.600. Con el saldo en dólares —92— habría cabido cualquier cosa
    // por debajo de 92 y se habrían rechazado retiros legítimos de miles de bolívares.
    expect(fn () => $solicitar->execute($user->id, [
        'tenant_id' => $this->tenant->id,
        'amount' => 5000.0,
        'payment_method' => 'Pago Móvil',
        'payment_details' => ['bank' => 'Banesco'],
    ]))->toThrow(Exception::class);
});

it('un retiro viejo en dólares no se resta como si fueran bolívares', function () {
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0);   // 4.600 Bs

    Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement::create([
        'id' => (string) Str::uuid(),
        'settlement_number' => 'PAY-VIEJO-001',
        'tenant_id' => $this->tenant->id,
        'type' => 'payout',
        'gross_sales_amount' => 50.0,
        'commission_amount' => 0.0,
        'net_amount' => 50.0,
        'currency' => 'USD',
        'status' => 'settled',
    ]);

    // Sin el filtro de moneda, esos 50 USD se restarían como 50 Bs: un descuadre silencioso
    // por el factor entero de la tasa. Quedan fuera del cálculo.
    expect($this->balance->requestable($this->tenant->id))->toBe(4600.0);
});

/**
 * Fase 4b: el saldo no es retirable hasta que el pedido llega a `delivered`.
 *
 * Protege del reembolso posterior al retiro: si la plataforma paga antes de que la mercancía
 * llegue y el comprador reclama después, el dinero ya salió y recuperarlo es perseguirlo.
 */
it('una venta cobrada pero no entregada todavía no se puede retirar', function () {
    comisionDe($this->tenant, 'pending', entregado: false);

    expect($this->balance->requestable($this->tenant->id))->toBe(0.0);
});

it('la wallet distingue los dos motivos de retención', function () {
    // Al comerciante le importa cuál es: uno depende de que la plataforma confirme el cobro
    // y el otro de que el paquete llegue. En el mismo saco sólo generan preguntas.
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0, entregado: true);          // 4.600 disponibles
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0, entregado: false);         // 4.600 esperando entrega
    comisionDe($this->tenant, 'awaiting_payment', total: 100.0, tasa: 50.0);                  // 4.600 esperando cobro

    $desglose = $this->balance->breakdown($this->tenant->id);

    expect($desglose['disponible_bs'])->toBe(4600.0)
        ->and($desglose['retenido_entrega_bs'])->toBe(4600.0)
        ->and($desglose['retenido_bs'])->toBe(4600.0);
});

it('entregar el pedido libera su comisión, que entra en el plazo de garantía', function () {
    $comision = comisionDe($this->tenant, 'pending', entregado: false);

    expect($this->balance->requestable($this->tenant->id))->toBe(0.0);

    app(Src\Monetization\Application\UseCases\ReleaseOrderCommissionUseCase::class)
        ->execute($comision->order_id);

    // Liberar no es lo mismo que retirable: la entrega abre el plazo en el que el comprador
    // todavía puede reclamar. El importe cambia de motivo de retención, no de bolsillo.
    $desglose = $this->balance->breakdown($this->tenant->id);

    expect($comision->refresh()->released_at)->not->toBeNull()
        ->and($desglose['retenido_entrega_bs'])->toBe(0.0)
        ->and($desglose['retenido_garantia_bs'])->toBe(4600.0)
        ->and($this->balance->requestable($this->tenant->id))->toBe(0.0);

    // Y cuando el plazo pasa, sí.
    $comision->update(['released_at' => now()->subDays(2)]);

    expect($this->balance->requestable($this->tenant->id))->toBe(4600.0);
});

it('liberar dos veces no mueve la fecha de la primera vez', function () {
    // Esa fecha es el rastro de cuándo el dinero pasó a ser reclamable. Un segundo envío
    // entregado del mismo pedido no puede reescribirla.
    $comision = comisionDe($this->tenant, 'pending', entregado: false);
    $liberar = app(Src\Monetization\Application\UseCases\ReleaseOrderCommissionUseCase::class);

    expect($liberar->execute($comision->order_id))->toBe(1);
    $primera = $comision->refresh()->released_at;

    expect($liberar->execute($comision->order_id))->toBe(0)
        ->and($comision->refresh()->released_at->equalTo($primera))->toBeTrue();
});

/**
 * Fase 4c: cada tienda cobra en el banco que quiera, pero si hace falta una transferencia
 * interbancaria su coste lo asume quien eligió esa vía.
 */
function ajustarPlataforma(string $banco, float $comision): void
{
    foreach (['central_pago_movil_bank_name' => $banco, 'central_interbank_transfer_fee' => (string) $comision] as $clave => $valor) {
        Src\Payment\Infrastructure\Eloquent\Models\CentralSetting::updateOrCreate(
            ['key' => $clave],
            ['group' => 'payment', 'value' => $valor]
        );
    }
}

function pedirRetiro(object $test, float $importe, string $banco): Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement
{
    return app(Src\Tenant\Application\UseCase\CreateTenantOwnerPayoutRequestUseCase::class)
        ->execute($test->duenno->id, [
            'tenant_id' => $test->tenant->id,
            'amount' => $importe,
            'payment_method' => 'Pago Móvil',
            'payment_details' => ['bank' => $banco],
        ]);
}

it('no cobra comisión si el retiro va al mismo banco de la plataforma', function () {
    ajustarPlataforma('Banesco', 100.0);
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0);   // 4.600 Bs
    $this->duenno = duenoDe($this->tenant);

    $retiro = pedirRetiro($this, 1000.0, 'Banesco');

    expect((float) $retiro->transfer_fee)->toBe(0.0)
        ->and((float) $retiro->net_amount)->toBe(1000.0);
});

it('cobra la comisión si el retiro va a otro banco', function () {
    ajustarPlataforma('Banesco', 100.0);
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0);
    $this->duenno = duenoDe($this->tenant);

    $retiro = pedirRetiro($this, 1000.0, 'Mercantil');

    expect((float) $retiro->transfer_fee)->toBe(100.0)
        // Sale de la wallet lo pedido; el comerciante recibe la diferencia.
        ->and((float) $retiro->gross_sales_amount)->toBe(1000.0)
        ->and((float) $retiro->net_amount)->toBe(900.0);
});

it('reconoce el mismo banco escrito de otra forma', function () {
    ajustarPlataforma('Banesco', 100.0);
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0);
    $this->duenno = duenoDe($this->tenant);

    // Mayúsculas y espacios de sobra no convierten a Banesco en otro banco.
    expect((float) pedirRetiro($this, 100.0, '  BANESCO ')->transfer_fee)->toBe(0.0);

    // Ni el «Banco» delante, que es lo que de verdad escribe la gente.
    expect((float) pedirRetiro($this, 100.0, 'Banco Banesco')->transfer_fee)->toBe(0.0);

    // Pero otro banco sigue siendo otro banco, con o sin prefijo.
    expect((float) pedirRetiro($this, 100.0, 'Banco Mercantil')->transfer_fee)->toBe(100.0);
});

it('el saldo baja lo pedido y no lo recibido: sin bolívares fantasma', function () {
    // El test que importa. Con la comisión descontándose del saldo por `net_amount`, al
    // comerciante le quedarían 100 Bs de saldo inventado después de cada retiro, y repetible.
    ajustarPlataforma('Banesco', 100.0);
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0);   // 4.600 Bs
    $this->duenno = duenoDe($this->tenant);

    pedirRetiro($this, 4000.0, 'Mercantil');

    // 4.600 - 4.000 = 600. NO 700, que es lo que saldría restando los 3.900 recibidos.
    expect($this->balance->requestable($this->tenant->id))->toBe(600.0);
});

it('rechaza un retiro que no cabe en el saldo, con la comisión ya contada', function () {
    ajustarPlataforma('Banesco', 100.0);
    comisionDe($this->tenant, 'pending', total: 100.0, tasa: 50.0);   // 4.600 Bs
    $this->duenno = duenoDe($this->tenant);

    expect(fn () => pedirRetiro($this, 5000.0, 'Mercantil'))->toThrow(Exception::class);
});

/**
 * La ventana de garantía: un pedido entregado espera un día antes de que su importe sea
 * retirable, para que al comprador le dé tiempo a pedir una devolución o reclamar. Si el
 * dinero ya salió, atender esa reclamación es perseguirlo.
 */
it('lo entregado hoy todavía no se puede retirar: el comprador está a tiempo de reclamar', function () {
    $comision = comisionDe($this->tenant, 'pending');
    $comision->update(['released_at' => now()]);

    expect($this->balance->requestable($this->tenant->id))->toBe(0.0);
    expect($this->balance->breakdown($this->tenant->id)['retenido_garantia_bs'])->toBe(4600.0);
});

it('pasado el plazo, lo entregado se vuelve retirable', function () {
    $comision = comisionDe($this->tenant, 'pending');
    $comision->update(['released_at' => now()->subDays(2)]);

    expect($this->balance->requestable($this->tenant->id))->toBe(4600.0);
    expect($this->balance->breakdown($this->tenant->id)['retenido_garantia_bs'])->toBe(0.0);
});

it('el plazo es configurable y cero es un valor legítimo', function () {
    // Cero significa «retirable al entregar», y hay que distinguirlo de «no configurado», que
    // cae al valor por defecto. Un `empty()` mal puesto los confundiría.
    Src\Payment\Infrastructure\Eloquent\Models\CentralSetting::updateOrCreate(
        ['key' => 'central_payout_hold_days'],
        ['group' => 'payment', 'value' => '0']
    );

    $comision = comisionDe($this->tenant, 'pending');
    $comision->update(['released_at' => now()]);

    expect($this->balance->requestable($this->tenant->id))->toBe(4600.0);
});

it('un plazo más largo retiene más tiempo', function () {
    Src\Payment\Infrastructure\Eloquent\Models\CentralSetting::updateOrCreate(
        ['key' => 'central_payout_hold_days'],
        ['group' => 'payment', 'value' => '7']
    );

    $comision = comisionDe($this->tenant, 'pending');
    $comision->update(['released_at' => now()->subDays(3)]);

    // Entregado hace tres días, pero el plazo es de siete.
    expect($this->balance->requestable($this->tenant->id))->toBe(0.0);
});
