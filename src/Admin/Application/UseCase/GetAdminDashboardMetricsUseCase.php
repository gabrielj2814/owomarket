<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use App\Models\CentralCustomer;
use App\Models\CentralOrder;
use App\Models\CommissionSettlement;
use App\Models\PlatformCommission;
use App\Models\SupportTicket;
use Src\ExchangeRate\Domain\Repositories\ExchangeRateRepositoryInterface;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class GetAdminDashboardMetricsUseCase
{
    public function __construct(
        private readonly ?ExchangeRateRepositoryInterface $exchangeRateRepository = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        // 1. Tasa de cambio activa
        $activeRate = 1.0;
        if ($this->exchangeRateRepository) {
            try {
                $rateModel = $this->exchangeRateRepository->getActiveRate();
                if ($rateModel) {
                    $activeRate = (float) $rateModel->getRate();
                }
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        // 2. Ventas y Comisiones
        $totalGmvUsd = 0.0;
        $totalCommissionUsd = 0.0;
        $totalOrdersCount = 0;
        $paidOrdersCount = 0;

        try {
            $totalGmvUsd = (float) CentralOrder::where('payment_status', 'paid')->sum('total_usd');
            $totalCommissionUsd = (float) PlatformCommission::where('status', 'settled')->sum('commission_amount');
            if ($totalCommissionUsd === 0.0) {
                $totalCommissionUsd = round($totalGmvUsd * 0.05, 2);
            }
            $totalOrdersCount = CentralOrder::count();
            $paidOrdersCount = CentralOrder::where('payment_status', 'paid')->count();
        } catch (\Throwable $e) {
            // Silently ignore if tables not seeded yet
        }

        // 3. Tiendas e Inquilinos
        $totalTenants = 0;
        $activeTenants = 0;
        $pendingTenants = 0;
        $suspendedTenants = 0;

        try {
            $totalTenants = Tenant::count();
            $activeTenants = Tenant::where(function ($q) {
                $q->where('status', 'active')->orWhere('request', 'approved');
            })->count();
            $pendingTenants = Tenant::where('request', 'in progress')->count();
            $suspendedTenants = Tenant::where('status', 'suspended')->count();
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // 4. Clientes Centrales
        $totalCustomers = 0;
        try {
            $totalCustomers = CentralCustomer::count();
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // 5. Payouts / Liquidaciones
        $pendingPayoutsCount = 0;
        $pendingPayoutsAmountUsd = 0.0;
        try {
            $pendingPayoutsQuery = CommissionSettlement::where('type', 'payout')->where('status', 'pending');
            $pendingPayoutsCount = $pendingPayoutsQuery->count();
            $pendingPayoutsAmountUsd = (float) $pendingPayoutsQuery->sum('net_amount');
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // 6. Tickets de Soporte
        $openTicketsCount = 0;
        $waitingTicketsCount = 0;
        try {
            $openTicketsCount = SupportTicket::whereIn('status', ['open', 'in_progress'])->count();
            $waitingTicketsCount = SupportTicket::where('status', 'waiting_reply')->count();
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // 7. Listas de actividad reciente
        $recentOrders = [];
        $recentTickets = [];
        $recentPayouts = [];

        try {
            $recentOrders = CentralOrder::orderBy('created_at', 'desc')->limit(5)->get();
            $recentTickets = SupportTicket::orderBy('created_at', 'desc')->limit(5)->get();
            $recentPayouts = CommissionSettlement::with('tenant')->where('type', 'payout')->orderBy('created_at', 'desc')->limit(5)->get();
        } catch (\Throwable $e) {
            // Silently ignore
        }

        return [
            'metrics' => [
                'total_gmv_usd' => round($totalGmvUsd, 2),
                'total_gmv_ves' => round($totalGmvUsd * $activeRate, 2),
                'total_commission_usd' => round($totalCommissionUsd, 2),
                'total_commission_ves' => round($totalCommissionUsd * $activeRate, 2),
                'total_orders_count' => $totalOrdersCount,
                'paid_orders_count' => $paidOrdersCount,
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'pending_tenants' => $pendingTenants,
                'suspended_tenants' => $suspendedTenants,
                'total_customers' => $totalCustomers,
                'pending_payouts_count' => $pendingPayoutsCount,
                'pending_payouts_amount_usd' => round($pendingPayoutsAmountUsd, 2),
                'open_tickets_count' => $openTicketsCount,
                'waiting_tickets_count' => $waitingTicketsCount,
                'active_exchange_rate' => $activeRate,
            ],
            'recent_activity' => [
                'orders' => $recentOrders,
                'tickets' => $recentTickets,
                'payouts' => $recentPayouts,
            ],
        ];
    }
}
