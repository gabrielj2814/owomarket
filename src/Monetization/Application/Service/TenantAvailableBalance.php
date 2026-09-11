<?php

declare(strict_types=1);

namespace Src\Monetization\Application\Service;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Throwable;

/**
 * El saldo de una tienda, en un solo sitio (hallazgo T1).
 *
 * Esto vivia como metodo privado dentro de CreateTenantOwnerPayoutRequestUseCase, asi que
 * la aprobacion del retiro —que esta en el modulo Admin— no podia usarlo. El resultado fue
 * que el saldo se comprobaba al PEDIR y nunca mas: ApproveCentralPayoutRequestUseCase solo
 * miraba que la solicitud existiera, estuviera 'pending' y trajera referencia bancaria.
 *
 * Se extrae aqui en vez de copiarlo al modulo Admin a proposito: dos copias de una formula
 * de saldo que divergen es como se pierde dinero, y este repositorio ya ha demostrado que
 * las copias divergen.
 *
 * **Son dos preguntas distintas, y confundirlas rompe uno de los dos flujos.**
 */
final class TenantAvailableBalance
{
    /**
     * Los unicos estados que son dinero del comerciante.
     *
     * Antes no habia filtro y la suma cogia el enum entero: una venta cancelada (`waived`) o
     * reembolsada (`refunded`) seguia contando como saldo retirable, y `awaiting_payment`
     * --el cobro que la plataforma todavia no ha confirmado-- tambien.
     *
     * Lista blanca y no lista negra a proposito: en dinero, un estado nuevo que nadie previo
     * tiene que quedarse FUERA del saldo, no colarse dentro.
     */
    private const ESTADOS_COBRADOS = ['pending', 'collected'];

    /**
     * Dias que un pedido entregado espera antes de que su importe sea retirable, si nadie ha
     * configurado otra cosa.
     *
     * Es la ventana de garantia del comprador: el plazo en el que todavia puede pedir una
     * devolucion o reclamar. Entregar y pagar en el mismo instante deja a la plataforma sin
     * margen para atender esa reclamacion con el dinero todavia en su cuenta.
     */
    private const DIAS_DE_RETENCION_POR_DEFECTO = 1;

    /**
     * Cuanto puede PEDIR ahora el comerciante.
     *
     * Descuenta los retiros ya pagados y tambien los pendientes: si no, pedir dos veces
     * seguidas el saldo entero colaria, porque la primera solicitud aun no se ha pagado.
     */
    public function requestable(string $tenantId, bool $lock = false): float
    {
        $ganancias = $this->netEarnings($tenantId);
        $pagados = $this->payouts($tenantId, ['settled'], $lock);
        $pendientes = $this->payouts($tenantId, ['pending'], $lock);

        return max(0.0, $ganancias - $pagados - $pendientes);
    }

    /**
     * Cuanto se puede PAGAR ahora, sin contar los retiros pendientes.
     *
     * Aqui NO se descuentan los pendientes, y es deliberado: al aprobar hay que compararlo
     * contra el importe de esta solicitud. Si se descontaran, la propia solicitud que se
     * esta aprobando estaria restada de su propio respaldo y no se aprobaria nunca; y con
     * dos pendientes a la vez se rechazarian las dos en vez de pagar la primera.
     *
     * Al aprobar en orden, cada una pasa a 'settled' y reduce este saldo para la siguiente,
     * que es justo el comportamiento que se quiere.
     */
    public function settleable(string $tenantId, bool $lock = false): float
    {
        return max(0.0, $this->netEarnings($tenantId) - $this->payouts($tenantId, ['settled'], $lock));
    }

    /**
     * El saldo en bolivares, valorado a la tasa congelada de CADA venta (Fase 1 del plan de
     * wallet y retiros). No se revaloriza al consultar: la plataforma le debe al comerciante
     * los bolivares que recibio del comprador, no los de hoy.
     *
     * @return array{disponible_bs: float, retenido_bs: float, retenido_entrega_bs: float, retenido_garantia_bs: float, retenido_fondo_bs: float, sin_valorar_usd: float, sin_valorar_count: int}
     */
    public function breakdown(string $tenantId): array
    {
        if (! Schema::hasTable('platform_commissions')) {
            return ['disponible_bs' => 0.0, 'retenido_bs' => 0.0, 'retenido_entrega_bs' => 0.0, 'retenido_garantia_bs' => 0.0, 'retenido_fondo_bs' => 0.0, 'sin_valorar_usd' => 0.0, 'sin_valorar_count' => 0];
        }

        $limite = now()->subDays($this->diasDeRetencion());

        // Tres situaciones distintas y no dos: entregado y con la garantia cumplida, entregado
        // pero dentro de la ventana, y sin entregar todavia.
        $enBolivares = fn (array $estados, ?string $entrega = null) => (float) $this->ventasDe($tenantId)
            ->whereIn('status', $estados)
            ->whereNotNull('exchange_rate')
            ->when($entrega === 'retirable', fn ($q) => $q->where('released_at', '<=', $limite))
            ->when($entrega === 'en_garantia', fn ($q) => $q->where('released_at', '>', $limite))
            ->when($entrega === 'sin_entregar', fn ($q) => $q->whereNull('released_at'))
            ->sum(DB::raw('(order_total - commission_amount) * exchange_rate'));

        // Sin tasa no se puede expresar en bolivares. Se muestran aparte en vez de
        // excluirlas en silencio: al comerciante no puede desaparecerle dinero sin
        // explicacion.
        $sinValorar = $this->ventasDe($tenantId)
            ->whereIn('status', self::ESTADOS_COBRADOS)
            ->whereNull('exchange_rate');

        return [
            // Ojo: esto es lo GANADO en bolivares, sin descontar retiros. Lo retirable es
            // `requestable()`, que es el unico numero contra el que se autoriza dinero.
            'disponible_bs' => $enBolivares(self::ESTADOS_COBRADOS, entrega: 'retirable'),
            // Dos motivos distintos para retener, y al comerciante le importa cual es: uno
            // depende de que la plataforma confirme el cobro y el otro de que el paquete
            // llegue. Meterlos en el mismo saco solo genera preguntas.
            'retenido_bs' => $enBolivares(['awaiting_payment']),
            'retenido_entrega_bs' => $enBolivares(self::ESTADOS_COBRADOS, entrega: 'sin_entregar'),
            // Entregado, pero el comprador todavia esta a tiempo de pedir una devolucion.
            'retenido_garantia_bs' => $enBolivares(self::ESTADOS_COBRADOS, entrega: 'en_garantia'),
            // Subsistema 4: el fondo de garantia. Se enseña aparte y no se suma en silencio a
            // los otros dos motivos de retencion porque es de naturaleza distinta --no espera
            // a un hecho, espera a que pase el plazo de reclamacion-- y sobre todo porque al
            // comerciante no puede bajarle el saldo sin decirle por que.
            'retenido_fondo_bs' => $this->reservaRetenida($tenantId),
            'sin_valorar_usd' => (float) (clone $sinValorar)->sum(DB::raw('order_total - commission_amount')),
            'sin_valorar_count' => (clone $sinValorar)->count(),
        ];
    }

    /**
     * Lo que la plataforma le debe a la tienda, **en bolivares**.
     *
     * El dolar es la unidad en la que se pone el precio; el comprador ve el precio en dolares
     * y su equivalente en bolivares a la tasa del dia, y paga bolivares. Nunca entra un dolar
     * a ninguna cuenta. Asi que el saldo no es "dolares convertidos": es la suma de los
     * bolivares que aporto cada venta --su total en USD por la tasa a la que compro ese
     * cliente--, y no hay nada que convertir al leer.
     *
     * Antes esta funcion devolvia dolares mientras la pantalla enseñaba bolivares, asi que el
     * comerciante veia bolivares y pedia dolares en el mismo formulario.
     */
    private function netEarnings(string $tenantId): float
    {
        if (! Schema::hasTable('platform_commissions')) {
            return 0.0;
        }

        $liberado = (float) $this->ventasDe($tenantId)
            ->whereIn('status', self::ESTADOS_COBRADOS)
            ->whereNotNull('exchange_rate')
            // Fase 4b: solo lo entregado, y con su ventana de garantia ya cumplida. Si la
            // plataforma paga antes y el comprador reclama despues, el dinero ya salio y
            // recuperarlo es perseguirlo.
            ->where('released_at', '<=', now()->subDays($this->diasDeRetencion()))
            ->sum(DB::raw('(order_total - commission_amount) * exchange_rate'));

        return $liberado - $this->reservaRetenida($tenantId);
    }

    /**
     * El saldo REAL de la tienda, sin recortar en cero. Puede ser negativo.
     *
     * `requestable()` y `settleable()` terminan en `max(0.0, ...)`, y eso esta bien para
     * autorizar dinero --nadie retira un negativo-- pero **destruye el dato justo cuando
     * importa**: una tienda que debe 4.600 Bs y una con saldo cero se ven identicas.
     *
     * Un negativo significa que la plataforma ya le pago mas de lo que resulto que le debia,
     * normalmente porque se reembolso una venta cuyo importe ya habia retirado. Ese hueco es
     * la deuda, y es lo que el subsistema 5 mide para saber cuanto pone la plataforma de su
     * bolsillo en una reclamacion.
     *
     * No confundir con `settleable()`: aquella responde «cuanto puedo pagar», esta responde
     * «cuanto vale realmente su posicion».
     */
    public function position(string $tenantId): float
    {
        return $this->netEarnings($tenantId) - $this->payouts($tenantId, ['settled'], false);
    }

    /**
     * Lo que la tienda le debe a la plataforma, en bolivares. Cero si no debe nada.
     */
    public function debt(string $tenantId): float
    {
        return max(0.0, -$this->position($tenantId));
    }

    /**
     * El fondo de garantia todavia retenido, en bolivares (subsistema 4).
     *
     * Se resta de lo liberado en vez de vivir en una tabla aparte, y eso es deliberado: el
     * saldo de una tienda tiene que salir de UN solo sitio. Una segunda tabla que hubiera que
     * restar por su cuenta es exactamente como nacen las dos consultas que responden a la
     * misma pregunta y acaban divergiendo.
     *
     * A la tasa congelada de cada venta, igual que el resto de la formula: se retiene una
     * parte de los bolivares que entraron, no un importe revalorizado a la tasa de hoy.
     */
    private function reservaRetenida(string $tenantId): float
    {
        $reserva = (float) $this->ventasDe($tenantId)
            ->whereIn('status', self::ESTADOS_COBRADOS)
            ->whereNotNull('exchange_rate')
            ->where('reserve_until', '>', now())
            ->sum(DB::raw('reserve_amount * exchange_rate'));

        // A centimos. Es dinero que se le enseña al comerciante y que se resta de lo que
        // puede retirar, asi que no puede salir con doce decimales: `9.20 * 50` da
        // `459.99999999999994` en coma flotante, y un saldo que no cuadra por una millonesima
        // es un saldo que alguien va a reportar como roto.
        return round($reserva, 2);
    }

    /**
     * Las ventas por las que la plataforma le debe dinero a esta tienda.
     *
     * Esta consulta no pinta una pantalla: **es la que autoriza cuanto dinero real sale**.
     *
     * **Aqui hubo un filtro `whereNotNull('central_order_id')`** que acotaba al canal central,
     * con este razonamiento: en el escaparate el comprador transferia directo al comerciante,
     * que ya tenia su dinero, asi que contarlo aqui le habria ofrecido retirar dos veces lo
     * mismo. Era correcto mientras cada canal cobraba por su lado.
     *
     * Desde que la plataforma cobra TODAS las ventas --tambien las del escaparate-- ese
     * razonamiento se invirtio: si la plataforma recibe ese dinero, se lo debe, y el filtro le
     * escondia al comerciante saldo que es suyo.
     *
     * Lo que si sigue filtrando esta el resto de la consulta: estado cobrable, tasa capturada
     * y entrega con su plazo cumplido.
     */
    private function ventasDe(string $tenantId): Builder
    {
        return PlatformCommission::query()->where('tenant_id', $tenantId);
    }

    /**
     * @param  array<int, string>  $estados
     */
    private function payouts(string $tenantId, array $estados, bool $lock): float
    {
        if (! Schema::hasTable('commission_settlements')) {
            return 0.0;
        }

        $consulta = CommissionSettlement::where('tenant_id', $tenantId)
            ->where('type', 'payout')
            // Los retiros que se restan tienen que estar en la misma moneda que el saldo. Sin
            // este filtro, una liquidacion vieja en USD se restaria como si fueran bolivares y
            // descuadraria el saldo por un factor de la tasa entera.
            // ponytail: los retiros en USD anteriores al cambio de unidad quedan fuera del
            // calculo. Son datos de desarrollo; si alguna vez hubiera retiros reales en USD
            // habria que decidir que se hace con ellos, no ignorarlos.
            ->where('currency', 'VES')
            ->whereIn('status', $estados);

        // Bloquea las filas de retiros de esta tienda mientras dure la transaccion. Sin
        // esto, dos peticiones simultaneas leen el mismo saldo, las dos pasan la
        // comprobacion y las dos se crean — el hallazgo C3 con otro nombre.
        if ($lock) {
            $consulta->lockForUpdate();
        }

        // Fase 4c: se resta lo que salio de la wallet, NO lo que llego al banco. Desde que
        // existe la comision por transferencia los dos importes divergen, y restar
        // `net_amount` le dejaria al comerciante la comision como saldo fantasma despues de
        // cada retiro. Repetible, y dinero que se crea solo.
        return (float) $consulta->sum('gross_sales_amount');
    }

    /**
     * Dias de garantia antes de que un pedido entregado sea retirable.
     *
     * Configurable desde los ajustes de cobro de la plataforma. Cero es un valor legitimo --lo
     * entregado se puede retirar en el acto-- asi que se distingue de "no configurado", que cae
     * al valor por defecto.
     */
    private function diasDeRetencion(): int
    {
        try {
            $valor = CentralSetting::query()
                ->where('group', 'payment')
                ->where('key', 'central_payout_hold_days')
                ->value('value');
        } catch (Throwable) {
            return self::DIAS_DE_RETENCION_POR_DEFECTO;
        }

        if ($valor === null || trim((string) $valor) === '') {
            return self::DIAS_DE_RETENCION_POR_DEFECTO;
        }

        return max(0, (int) $valor);
    }
}
