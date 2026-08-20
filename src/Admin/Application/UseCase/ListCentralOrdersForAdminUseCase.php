<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use App\Models\CentralOrder;
use App\Models\CentralOrderItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class ListCentralOrdersForAdminUseCase
{
    /**
     * @param array{
     *     tenant_id?: string|null,
     *     status?: string|null,
     *     payment_status?: string|null,
     *     search?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     per_page?: int,
     *     page?: int
     * } $filters
     * @return array{
     *     orders: LengthAwarePaginator,
     *     metrics: array{
     *         total_orders: int,
     *         total_gmv_usd: float,
     *         total_gmv_ves: float,
     *         paid_orders_count: int,
     *         pending_orders_count: int,
     *         cancelled_orders_count: int
     *     },
     *     tenants: array<array{id: string, name: string}>
     * }
     */
    public function execute(array $filters): array
    {
        $query = CentralOrder::query()
            ->with(['items', 'customer']);

        if (! empty($filters['tenant_id'])) {
            $tenantId = $filters['tenant_id'];
            $query->where(function ($q) use ($tenantId) {
                $q->where('metadata->tenant_id', $tenantId)
                    ->orWhereHas('items', function ($iq) use ($tenantId) {
                        $iq->where('tenant_id', $tenantId);
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Métricas Globales
        $totalOrders = CentralOrder::count();
        $totalGmvUsd = (float) CentralOrder::where('payment_status', 'paid')->sum('total');
        if ($totalGmvUsd === 0.0) {
            $totalGmvUsd = (float) CentralOrder::sum('total');
        }
        $totalGmvVes = 0.0;
        $paidOrdersCount = CentralOrder::where('payment_status', 'paid')->count();
        $pendingOrdersCount = CentralOrder::whereIn('status', ['pending', 'processing'])->count();
        $cancelledOrdersCount = CentralOrder::whereIn('status', ['cancelled', 'refunded'])->count();

        $tenantsList = Tenant::select('id', 'name')->orderBy('name', 'asc')->get()->toArray();

        return [
            'orders' => $orders,
            'metrics' => [
                'total_orders' => $totalOrders,
                'total_gmv_usd' => round($totalGmvUsd, 2),
                'total_gmv_ves' => round($totalGmvVes, 2),
                'paid_orders_count' => $paidOrdersCount,
                'pending_orders_count' => $pendingOrdersCount,
                'cancelled_orders_count' => $cancelledOrdersCount,
            ],
            'tenants' => $tenantsList,
        ];
    }
}
