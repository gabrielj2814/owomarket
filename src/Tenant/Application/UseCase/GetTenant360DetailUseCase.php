<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicket;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class GetTenant360DetailUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $tenantId): array
    {
        $tenant = Tenant::with(['domains', 'owners'])->find($tenantId);

        if (! $tenant) {
            throw new Exception("Tienda inquilina '{$tenantId}' no encontrada.", 404);
        }

        // 1. Métricas de Ventas y Órdenes
        $totalSalesUsd = 0.0;
        $ordersCount = 0;
        $totalCommissionsUsd = 0.0;
        $productsPublishedCount = 0;

        try {
            $totalSalesUsd = (float) CentralOrderItem::where('tenant_id', $tenantId)->sum('total_usd');
            if ($totalSalesUsd === 0.0) {
                $totalSalesUsd = (float) CentralOrder::where('tenant_id', $tenantId)->where('payment_status', 'paid')->sum('total_usd');
            }
            $ordersCount = CentralOrder::where('tenant_id', $tenantId)->count();
            $totalCommissionsUsd = (float) PlatformCommission::where('tenant_id', $tenantId)->where('status', 'settled')->sum('commission_amount');
            $productsPublishedCount = CentralProduct::where('tenant_id', $tenantId)->where('is_active', true)->count();
        } catch (\Throwable $e) {
            // Silently handle if tables empty
        }

        // 2. Liquidaciones / Payouts
        $payouts = [];
        $totalPayoutsSettledUsd = 0.0;
        $totalPayoutsPendingUsd = 0.0;

        try {
            $payouts = CommissionSettlement::where('tenant_id', $tenantId)
                ->where('type', 'payout')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $totalPayoutsSettledUsd = (float) CommissionSettlement::where('tenant_id', $tenantId)
                ->where('type', 'payout')
                ->where('status', 'settled')
                ->sum('net_amount');

            $totalPayoutsPendingUsd = (float) CommissionSettlement::where('tenant_id', $tenantId)
                ->where('type', 'payout')
                ->where('status', 'pending')
                ->sum('net_amount');
        } catch (\Throwable $e) {
            // Silently handle
        }

        // 3. Tickets de Soporte
        $tickets = [];
        $openTicketsCount = 0;

        try {
            $tickets = SupportTicket::where('tenant_id', $tenantId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $openTicketsCount = SupportTicket::where('tenant_id', $tenantId)
                ->whereIn('status', ['open', 'in_progress', 'waiting_reply'])
                ->count();
        } catch (\Throwable $e) {
            // Silently handle
        }

        // 4. Órdenes Recientes
        $recentOrders = [];
        try {
            $recentOrders = CentralOrder::where('tenant_id', $tenantId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            // Silently handle
        }

        return [
            'tenant' => $tenant,
            'metrics' => [
                'total_sales_usd' => round($totalSalesUsd, 2),
                'total_orders_count' => $ordersCount,
                'total_commissions_usd' => round($totalCommissionsUsd, 2),
                'products_published_count' => $productsPublishedCount,
                'total_payouts_settled_usd' => round($totalPayoutsSettledUsd, 2),
                'total_payouts_pending_usd' => round($totalPayoutsPendingUsd, 2),
                'open_tickets_count' => $openTicketsCount,
            ],
            'recent_orders' => $recentOrders,
            'payouts' => $payouts,
            'tickets' => $tickets,
        ];
    }
}
