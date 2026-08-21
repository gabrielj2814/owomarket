<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;

final class ListCustomerOrdersUseCase
{
    /**
     * @param  array{status?: string|null, search?: string|null, limit?: int|null, page?: int|null}  $filters
     * @return array{data: array<int, mixed>, total: int, current_page: int, last_page: int}
     */
    public function execute(string $customerId, ?string $customerEmail = null, array $filters = []): array
    {
        $query = CentralOrder::with('items')
            ->where(function ($q) use ($customerId, $customerEmail) {
                $q->where('customer_id', $customerId);
                if ($customerEmail) {
                    $q->orWhere('customer_email', strtolower(trim($customerEmail)));
                }
            });

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', $search)
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('product_name', 'like', $search);
                    });
            });
        }

        $limit = isset($filters['limit']) && $filters['limit'] > 0 ? (int) $filters['limit'] : 10;
        $paginator = $query->orderBy('created_at', 'desc')->paginate($limit);

        return [
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
