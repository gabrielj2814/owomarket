<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\ExchangeRate\Application\UseCase\GetActiveExchangeRateUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantSubscription;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Throwable;

final class CalculateAndRecordOrderCommissionUseCase
{
    public function __construct(
        private readonly GetActiveExchangeRateUseCase $tasaActiva
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        string $tenantId,
        string $orderId,
        string $orderNumber,
        float $orderTotal,
        ?string $paymentGateway = null,
        string $currency = 'USD',
        /**
         * Hallazgo N15: la comision nace devengada pero NO cobrable. Solo se pone en
         * `pending` —y por tanto entra en la siguiente liquidacion— cuando el pago esta
         * confirmado. Antes nacia en `pending` sin mirar el `payment_status`, que para
         * pago_movil, transferencia manual y contra entrega es siempre `pending`.
         */
        bool $paid = false,
        array $metadata = [],
        /**
         * Hallazgo Auditoria #1: `$orderId` es el pedido de la TIENDA. El del pedido
         * central va aparte, porque son identificadores de bases distintas y meterlos en
         * la misma columna era lo que dejaba `$centralOrder->commissions` siempre vacio.
         *
         * Opcional porque el checkout del storefront no pasa por ningun pedido central.
         */
        ?string $centralOrderId = null
    ): PlatformCommission {
        // 1. Resolve applicable commission rate based on 3-tier hierarchy:
        // Priority 1: Tenant specific custom_commission_rate
        // Priority 2: Active subscription plan commission_rate
        // Priority 3: Platform global default rate (8.00%)
        $rate = $this->resolveCommissionRate($tenantId);

        $commissionAmount = round($orderTotal * ($rate / 100), 2);

        return PlatformCommission::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'order_id' => $orderId,
            'central_order_id' => $centralOrderId,
            'order_number' => $orderNumber,
            'order_total' => $orderTotal,
            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
            'currency' => $currency,
            'exchange_rate' => $this->capturarTasa(),
            'status' => $paid ? 'pending' : 'awaiting_payment',
            'payment_gateway' => $paymentGateway,
            'metadata' => array_merge($metadata, [
                'resolved_rate_source' => $this->resolveRateSource($tenantId),
            ]),
        ]);
    }

    /**
     * Fase 1 del plan de wallet y retiros: la tasa se congela en el momento de la venta.
     *
     * La wallet de la tienda guarda su saldo en bolivares a la tasa del dia en que vendio,
     * asi que la plataforma le debe exactamente los bolivares que recibio del comprador. Sin
     * capturarla aqui, ese saldo ya no se puede congelar despues: el dato no existe en
     * ninguna otra parte.
     *
     * Va en este caso de uso porque es el punto por donde pasan los DOS canales -- el
     * checkout del escaparate y el despacho central. Una captura, no dos.
     *
     * Devuelve null y no revienta si no hay tasa activa: una venta no puede caerse porque el
     * BCV no haya sincronizado. Queda sin valorar, y que hacer con esas comisiones es la
     * Fase 2.
     */
    private function capturarTasa(): ?float
    {
        try {
            return $this->tasaActiva->execute()->getRate()->value();
        } catch (Throwable $e) {
            Log::warning('Comision registrada sin tasa de cambio: no sera retirable hasta valorarla.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function resolveCommissionRate(string $tenantId): float
    {
        $tenant = Tenant::find($tenantId);

        // Priority 1: Custom tenant rate
        if ($tenant) {
            $customRate = $tenant->custom_commission_rate
                ?? $tenant->getAttribute('custom_commission_rate')
                ?? ($tenant->data['custom_commission_rate'] ?? null);

            if ($customRate !== null && is_numeric($customRate)) {
                return (float) $customRate;
            }
        }

        // Priority 2: Active subscription plan
        $subscription = TenantSubscription::with('plan')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        if ($subscription && $subscription->isActive() && $subscription->plan) {
            return (float) $subscription->plan->commission_rate;
        }

        // Priority 3: Global default rate (8.00%)
        return 8.00;
    }

    private function resolveRateSource(string $tenantId): string
    {
        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            $customRate = $tenant->custom_commission_rate
                ?? $tenant->getAttribute('custom_commission_rate')
                ?? ($tenant->data['custom_commission_rate'] ?? null);

            if ($customRate !== null && is_numeric($customRate)) {
                return 'tenant_custom';
            }
        }

        $subscription = TenantSubscription::with('plan')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        if ($subscription && $subscription->isActive() && $subscription->plan) {
            return 'subscription_plan:'.$subscription->plan->slug;
        }

        return 'platform_default';
    }
}
