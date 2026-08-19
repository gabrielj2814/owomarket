<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use App\Models\CentralCustomerWishlist;
use Illuminate\Database\Eloquent\Collection;

final class ListCustomerWishlistUseCase
{
    /**
     * @return Collection<int, CentralCustomerWishlist>
     */
    public function execute(string $customerId): Collection
    {
        return CentralCustomerWishlist::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
