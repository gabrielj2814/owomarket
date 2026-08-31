<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Illuminate\Support\Facades\Schema;
use Src\Monetization\Application\Service\TenantAvailableBalance;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;
use Throwable;

final class GetTenantOwnerWalletSummaryUseCase
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership,
        private readonly TenantAvailableBalance $balance
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $userId): array
    {
        // Sólo las tiendas del propio usuario. Si no tiene ninguna, el resumen va en cero:
        // NUNCA se cae hacia las tiendas de otros comerciantes.
        $tenants = $this->ownership->tenantsOf($userId);

        try {
            $ajustes = CentralSetting::query()->where('group', 'payment')->pluck('value', 'key')->all();
        } catch (Throwable) {
            $ajustes = [];
        }
        $tenantIds = $tenants->pluck('id')->map(fn ($id) => (string) $id)->all();

        $grossSales = 0.0;
        $totalCommissions = 0.0;
        $settledPayouts = 0.0;
        $pendingPayouts = 0.0;
        $availableBalance = 0.0;
        $settlements = [];
        $bolivares = ['disponible_bs' => 0.0, 'retenido_bs' => 0.0, 'retenido_entrega_bs' => 0.0, 'sin_valorar_usd' => 0.0, 'sin_valorar_count' => 0];

        if ($tenantIds !== []) {
            if (Schema::hasTable('platform_commissions')) {
                // Fase 2: aqui habia una copia de la formula del saldo, con su propia resta y
                // sin los filtros de canal y estado. El docblock de `TenantAvailableBalance`
                // ya avisaba de esto --"dos copias de una formula de saldo que divergen es
                // como se pierde dinero"--, y la copia seguia viva justo al lado.
                //
                // Ahora la pantalla y la autorizacion del retiro leen el mismo numero.
                $ventas = PlatformCommission::whereIn('tenant_id', $tenantIds)
                    ->whereNotNull('central_order_id')
                    ->whereIn('status', ['pending', 'collected']);

                $grossSales = (float) (clone $ventas)->sum('order_total');
                $totalCommissions = (float) (clone $ventas)->sum('commission_amount');

                foreach ($tenantIds as $tenantId) {
                    $availableBalance += $this->balance->requestable($tenantId);

                    $desglose = $this->balance->breakdown($tenantId);
                    $bolivares['disponible_bs'] += $desglose['disponible_bs'];
                    $bolivares['retenido_bs'] += $desglose['retenido_bs'];
                    $bolivares['retenido_entrega_bs'] += $desglose['retenido_entrega_bs'];
                    $bolivares['sin_valorar_usd'] += $desglose['sin_valorar_usd'];
                    $bolivares['sin_valorar_count'] += $desglose['sin_valorar_count'];
                }
            }

            if (Schema::hasTable('commission_settlements')) {
                $settledPayouts = (float) CommissionSettlement::whereIn('tenant_id', $tenantIds)
                    ->where('type', 'payout')
                    ->where('status', 'settled')
                    ->sum('net_amount');

                $pendingPayouts = (float) CommissionSettlement::whereIn('tenant_id', $tenantIds)
                    ->where('type', 'payout')
                    ->where('status', 'pending')
                    ->sum('net_amount');

                $settlements = CommissionSettlement::whereIn('tenant_id', $tenantIds)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($s) {
                        return [
                            'id' => $s->id,
                            'settlement_number' => $s->settlement_number,
                            'tenant_id' => $s->tenant_id,
                            'type' => $s->type,
                            'amount' => (float) $s->net_amount,
                            // El historial mezcla retiros (VES) con liquidaciones de comision
                            // del escaparate (USD). Cada fila lleva su moneda en vez de que la
                            // pantalla asuma una.
                            'currency' => (string) ($s->currency ?? 'USD'),
                            // Sin `amount_ves`: se calculaba con la tasa fija, asi que era un
                            // numero inventado. La liquidacion guarda su importe en USD y
                            // expresarlo en bolivares con la tasa de hoy seria revalorizar
                            // justo lo que este modelo congela. Lo resuelve la Fase 3, cuando
                            // el retiro registre sus propios bolivares.
                            'status' => $s->status,
                            'payment_method' => $s->payment_method ?? 'Pago Móvil',
                            'payment_reference' => $s->payment_reference,
                            'date' => $s->created_at?->format('d/m/Y H:i'),
                        ];
                    })
                    ->toArray();
            }
        }

        return [
            'gross_sales' => $grossSales,
            'total_commissions' => $totalCommissions,
            // En BOLIVARES, que es lo que se retira. `gross_sales` y `total_commissions`
            // siguen en USD a proposito: son la unidad en la que se pusieron los precios, y
            // le sirven al comerciante de referencia. Pero el dinero es bolivares.
            'available_balance' => round($availableBalance, 2),
            'retained_ves' => round($bolivares['retenido_bs'], 2),
            'retained_delivery_ves' => round($bolivares['retenido_entrega_bs'], 2),
            'unvalued_usd' => round($bolivares['sin_valorar_usd'], 2),
            'unvalued_count' => $bolivares['sin_valorar_count'],
            'pending_payouts' => $pendingPayouts,
            'settled_payouts' => $settledPayouts,
            // La pantalla lo necesita para pedir un retiro. Antes lo adivinaba de la primera
            // liquidacion, asi que una tienda sin liquidaciones previas no podia pedir la
            // primera.
            'tenant_id' => $tenantIds[0] ?? null,
            // Fase 4c: la pantalla necesita los dos para avisar del descuento ANTES de que el
            // comerciante confirme. Un retiro que llega mermado sin aviso es una reclamacion.
            'platform_bank' => $ajustes['central_pago_movil_bank_name'] ?? null,
            'interbank_transfer_fee' => (float) ($ajustes['central_interbank_transfer_fee'] ?? 0.0),
            'tenants_count' => count($tenantIds),
            'settlements' => $settlements,
        ];
    }
}
