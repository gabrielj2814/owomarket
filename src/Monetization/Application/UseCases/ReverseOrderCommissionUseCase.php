<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Throwable;

/**
 * Revierte la comisión de la plataforma cuando un pedido se cancela o se
 * reembolsa (hallazgo D2).
 *
 * El problema: `CalculateAndRecordOrderCommissionUseCase` crea la comisión con
 * `status = 'pending'` en el momento del despacho, **sin importar el
 * `payment_status`** — y para `pago_movil`, `manual_transfer` y
 * `cash_on_delivery` ese estado es siempre `pending`. No existía ninguna ruta
 * que la pusiera en otro estado: `CancelOrderUseCase` y `RefundOrderUseCase`
 * sólo mutaban el agregado `Order` de la tienda y no tocaban la base central.
 *
 * Escenario de la auditoría: un cliente pide $1.000 con Pago Móvil y nunca
 * paga. La tienda cancela. La comisión de $80 seguía pendiente y
 * `GenerateTenantCommissionSettlementUseCase` la incluía en la siguiente
 * liquidación: se le cobraban $80 a la tienda por una venta que no existió.
 *
 * Estados usados (los que ya define la tabla `platform_commissions`):
 *   - `waived`   → pedido cancelado: la venta nunca ocurrió, la plataforma
 *                  renuncia a la comisión.
 *   - `refunded` → pedido reembolsado: la venta ocurrió y se deshizo.
 */
/*
 * Sin `final`: los tests la sustituyen por un doble de Mockery. Es la regla del
 * proyecto para los colaboradores que se doblan en tests (ver `reglas.md`), no una
 * excepcion.
 */
class ReverseOrderCommissionUseCase
{
    public const REASON_CANCELLED = 'order_cancelled';

    public const REASON_REFUNDED = 'order_refunded';

    /**
     * @param  string  $orderId  ID del pedido DE LA TIENDA (es lo que guarda
     *                           PlatformCommission.order_id).
     * @return int Número de comisiones revertidas.
     */
    public function execute(string $orderId, string $reason, ?string $notes = null): int
    {
        $newStatus = $reason === self::REASON_REFUNDED ? 'refunded' : 'waived';

        try {
            $commissions = PlatformCommission::where('order_id', $orderId)
                // Hallazgo N15: se incluye `awaiting_payment`. Desde que la comision
                // nace devengada pero no cobrable, cancelar un pedido cuyo pago nunca se
                // confirmo tiene que anularla igual — si no, se quedaria viva esperando
                // un pago que ya no va a llegar.
                ->whereIn('status', ['awaiting_payment', 'pending', 'collected'])
                ->get();
        } catch (Throwable $e) {
            // La comisión vive en la base central; si no está accesible desde
            // este contexto no se puede impedir la cancelación del pedido, pero
            // el fallo tiene que quedar registrado y no pasar inadvertido.
            Log::error('No se pudo consultar la comisión para revertirla', [
                'order_id' => $orderId,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }

        $reversed = 0;

        foreach ($commissions as $commission) {
            // Caso delicado: la comisión ya está liquidada o cobrada. No se
            // puede "deshacer" sin más, porque puede que al comerciante ya se
            // le haya descontado. Se marca igual —para que no vuelva a
            // liquidarse— pero se deja señalado que requiere un ajuste manual
            // (una nota de crédito en la siguiente liquidación).
            $requiresManualAdjustment = $commission->settlement_id !== null
                || $commission->status === 'collected';

            $metadata = $commission->metadata ?? [];
            $metadata['reversal'] = [
                'reason' => $reason,
                'previous_status' => $commission->status,
                'previous_settlement_id' => $commission->settlement_id,
                'reversed_at' => now()->toIso8601String(),
                'notes' => $notes,
                'requires_manual_adjustment' => $requiresManualAdjustment,
            ];

            $commission->status = $newStatus;
            $commission->metadata = $metadata;
            $commission->save();

            // Hallazgo N16: no existian notas de credito. Revertir una comision **ya
            // liquidada** solo dejaba una marca `requires_manual_adjustment` y un aviso en
            // el log: alguien tenia que acordarse de descontarsela al comerciante a mano.
            //
            // Ahora se emite una comision de importe NEGATIVO, cobrable y sin liquidar,
            // que la siguiente liquidacion suma como cualquier otra y por tanto compensa
            // sola. No hace falta tabla nueva: una nota de credito es una comision al
            // reves.
            if ($requiresManualAdjustment) {
                $this->issueCreditNote($commission, $reason, $notes);
            }

            $reversed++;

            if ($requiresManualAdjustment) {
                Log::warning('Comisión revertida sobre una liquidación ya emitida: requiere ajuste manual', [
                    'commission_id' => $commission->id,
                    'tenant_id' => $commission->tenant_id,
                    'order_id' => $orderId,
                    'settlement_id' => $commission->settlement_id,
                    'amount' => $commission->commission_amount,
                ]);
            }
        }

        return $reversed;
    }

    /**
     * Emite la nota de credito que compensa una comision ya liquidada (hallazgo N16).
     *
     * Es una `PlatformCommission` normal con el importe en negativo, `pending` y sin
     * `settlement_id`, asi que `GenerateTenantCommissionSettlementUseCase` la recoge y la
     * suma igual que a las demas: el neto de la siguiente liquidacion ya sale corregido.
     */
    private function issueCreditNote(PlatformCommission $original, string $reason, ?string $notes): void
    {
        PlatformCommission::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $original->tenant_id,
            'order_id' => $original->order_id,
            'order_number' => $original->order_number,
            // El total del pedido tambien va en negativo para que las metricas de venta
            // bruta de la liquidacion no cuenten dos veces la misma venta.
            'order_total' => -1 * (float) $original->order_total,
            'commission_rate' => $original->commission_rate,
            'commission_amount' => -1 * (float) $original->commission_amount,
            'currency' => $original->currency,
            'status' => 'pending',
            'payment_gateway' => $original->payment_gateway,
            'settlement_id' => null,
            'metadata' => [
                'credit_note' => true,
                'reverses_commission_id' => $original->id,
                'reverses_settlement_id' => $original->metadata['reversal']['previous_settlement_id'] ?? null,
                'reason' => $reason,
                'notes' => $notes,
                'issued_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
