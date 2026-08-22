<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;

final class ListCustomerReturnRequestsUseCase
{
    /**
     * @return Collection<int, CustomerReturnRequest>
     */
    public function execute(string $customerId, ?string $customerEmail = null): Collection
    {
        return CustomerReturnRequest::where(function ($q) use ($customerId, $customerEmail) {
            $q->where('customer_id', $customerId);
            if ($customerEmail) {
                $q->orWhere('customer_email', strtolower(trim($customerEmail)));
            }
        })
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
