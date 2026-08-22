<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantSubscription;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class CalculateAndRecordOrderCommissionUseCase
{
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
        array $metadata = []
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
            'order_number' => $orderNumber,
            'order_total' => $orderTotal,
            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
            'currency' => $currency,
            'status' => $paid ? 'pending' : 'awaiting_payment',
            'payment_gateway' => $paymentGateway,
            'metadata' => array_merge($metadata, [
                'resolved_rate_source' => $this->resolveRateSource($tenantId),
            ]),
        ]);
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
