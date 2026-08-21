<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerAddress;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListCentralCustomersForAdminUseCase
{
    /**
     * @param array{
     *     search?: string|null,
     *     is_active?: string|bool|null,
     *     per_page?: int,
     *     page?: int
     * } $filters
     * @return array{
     *     customers: LengthAwarePaginator,
     *     metrics: array{
     *         total_customers: int,
     *         active_customers: int,
     *         blocked_customers: int,
     *         customers_with_orders: int
     *     }
     * }
     */
    public function execute(array $filters): array
    {
        $query = CentralCustomer::query()
            ->with(['addresses'])
            ->withCount(['orders', 'supportTickets']);

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $customers = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Métricas
        $totalCustomers = CentralCustomer::count();
        $activeCustomers = CentralCustomer::where('is_active', true)->count();
        $blockedCustomers = CentralCustomer::where('is_active', false)->count();
        $customersWithOrders = CentralCustomer::has('orders')->count();

        return [
            'customers' => $customers,
            'metrics' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'blocked_customers' => $blockedCustomers,
                'customers_with_orders' => $customersWithOrders,
            ],
        ];
    }
}
