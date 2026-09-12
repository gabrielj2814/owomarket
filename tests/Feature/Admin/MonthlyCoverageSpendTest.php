<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\Service\MonthlyCoverageSpend;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * El techo mensual de alarma (decisión de garantías).
 *
 * `platform_covered_amount` se escribía en cada reclamación desde la fase B y **nadie lo sumaba
 * nunca**: el mes se podía descontrolar entero sin que hubiera dónde verlo.
 *
 * Lo que se vigila aquí es sobre todo **la conversión**. El importe se guarda en bolívares a la
 * tasa congelada de cada venta, así que sumarlos tal cual da un número que no corresponde a
 * ningún dinero real — y que se queda corto siempre, porque las ventas viejas tienen tasa más
 * baja. Un termómetro sesgado hacia no avisar es peor que no tener termómetro.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    // `platform_commissions.tenant_id` tiene clave foránea: sin la tienda, el fixture no entra.
    if (! ModelsTenant::where('id', 'tienda-techo')->exists()) {
        ModelsTenant::create([
            'id' => 'tienda-techo',
            'name' => 'Tienda Techo',
            'slug' => 'tienda-techo',
            'status' => 'active',
            'request' => 'approved',
        ]);
    }

    $this->spend = new MonthlyCoverageSpend;
});

/**
 * Una reclamación resuelta que le costó `$dolares` a la plataforma, sobre una venta cuya tasa
 * congelada fue `$tasa`.
 */
function coberturaDe(float $dolares, float $tasa, string $cuando = 'now'): void
{
    $tenantOrderId = (string) Str::uuid();

    PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => 'tienda-techo',
        'order_id' => $tenantOrderId,
        'order_number' => 'ORD-'.strtoupper(Str::random(5)),
        'order_total' => $dolares,
        'commission_rate' => 8.0,
        'commission_amount' => $dolares * 0.08,
        'currency' => 'USD',
        'exchange_rate' => $tasa,
        'status' => 'collected',
    ]);

    CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_source' => 'central',
        'order_number' => 'ORD-'.strtoupper(Str::random(5)),
        'tenant_order_id' => $tenantOrderId,
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'comprador@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Producto',
        'tenant_id' => 'tienda-techo',
        'reason' => 'Producto dañado o roto',
        'description' => 'Da igual para esta cuenta.',
        'status' => 'approved',
        'resolved_at' => now()->parse($cuando),
        'resolved_by' => 'merchant',
        // Lo que de verdad se mide: bolívares, a la tasa de ESA venta.
        'platform_covered_amount' => $dolares * $tasa,
    ]);
}

it('convierte cada fila con su propia tasa, no el total con la de hoy', function () {
    /*
     * EL TEST QUE IMPORTA. Tres coberturas de exactamente $100 cada una, de ventas con tasas
     * muy distintas. En bolívares suman 227.500 --un número que no es de nadie-- y dividir ese
     * total por cualquier tasa única da algo distinto de $300.
     */
    coberturaDe(dolares: 100.0, tasa: 600.0);
    coberturaDe(dolares: 100.0, tasa: 775.0);
    coberturaDe(dolares: 100.0, tasa: 900.0);

    expect($this->spend->currentMonth()['spent_usd'])->toBe(300.0);
});

it('no cuenta como cero una cobertura sin tasa', function () {
    /*
     * Un pedido sin comisión registrada no tiene tasa. Contarlo como cero diría «este mes no se
     * gastó», que es lo contrario de «no sabemos cuánto» -- y en una alarma esa diferencia es
     * justo la que hace que no suene.
     */
    coberturaDe(dolares: 100.0, tasa: 775.0);

    CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_source' => 'storefront',
        'order_number' => 'ORD-SIN-COMISION',
        'tenant_order_id' => (string) Str::uuid(),
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'comprador@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Producto',
        'tenant_id' => 'tienda-techo',
        'reason' => 'Otro motivo',
        'description' => 'Sin comision registrada.',
        'status' => 'approved',
        'resolved_at' => now(),
        'resolved_by' => 'merchant',
        'platform_covered_amount' => 50000.0,
    ]);

    // Solo la que sí tiene tasa. La otra no suma ni resta: se queda fuera.
    expect($this->spend->currentMonth()['spent_usd'])->toBe(100.0);
    expect($this->spend->lastMonths()[0]['claims'])->toBe(1);
});

it('marca el mes que pasa del techo sin cortar nada', function () {
    CentralSetting::create([
        'id' => (string) Str::uuid(),
        'group' => 'payment',
        'key' => 'central_claim_monthly_alarm_usd',
        'value' => '250',
    ]);

    coberturaDe(dolares: 300.0, tasa: 775.0);

    $mes = $this->spend->currentMonth();

    expect($mes['over'])->toBeTrue();
    expect($mes['threshold_usd'])->toBe(250.0);
    // No hay nada que «cortar» que comprobar: el servicio solo informa. Es deliberado -- si
    // fuera un muro, los compradores de fin de mes se quedarian sin garantia por un motivo que
    // no tiene nada que ver con su compra.
    expect($mes['spent_usd'])->toBe(300.0);
});

it('un mes que se paso sigue viendose despues, sin haber guardado ningun aviso', function () {
    /*
     * Por esto no hay comando ni tabla: la alarma se deriva de datos que ya no se mueven, asi
     * que no depende de que alguien mirara ese dia.
     */
    CentralSetting::create([
        'id' => (string) Str::uuid(),
        'group' => 'payment',
        'key' => 'central_claim_monthly_alarm_usd',
        'value' => '100',
    ]);

    coberturaDe(dolares: 500.0, tasa: 775.0, cuando: now()->subMonthsNoOverflow(2)->startOfMonth()->addDay()->toDateTimeString());

    $meses = collect($this->spend->lastMonths());

    expect($meses->firstWhere('over', true))->not->toBeNull();
    expect($meses->firstWhere('over', true)['spent_usd'])->toBe(500.0);
    // El mes en curso, en cambio, no se pasó: son cosas distintas y no se confunden.
    expect($this->spend->currentMonth()['over'])->toBeFalse();
});

it('solo cuenta reclamaciones resueltas con cobertura', function () {
    // Una abierta todavía no ha costado nada, y una aprobada que el saldo de la tienda cubrió
    // entera tampoco. Contarlas inflaría la alarma hasta volverla inútil.
    CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_source' => 'central',
        'order_number' => 'ORD-ABIERTA',
        'tenant_order_id' => (string) Str::uuid(),
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'comprador@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Producto',
        'tenant_id' => 'tienda-techo',
        'reason' => 'Otro motivo',
        'description' => 'Sigue abierta.',
        'status' => 'requested',
        'platform_covered_amount' => 0,
    ]);

    expect($this->spend->currentMonth()['spent_usd'])->toBe(0.0);
});

it('devuelve seis meses aunque no haya pasado nada', function () {
    $meses = $this->spend->lastMonths();

    expect($meses)->toHaveCount(6);
    expect($meses[0]['spent_usd'])->toBe(0.0);
    expect($meses[0]['over'])->toBeFalse();
    // El techo por defecto es el que se usa mientras nadie configure otro.
    expect($this->spend->threshold())->toBe(MonthlyCoverageSpend::TECHO_POR_DEFECTO);
});
