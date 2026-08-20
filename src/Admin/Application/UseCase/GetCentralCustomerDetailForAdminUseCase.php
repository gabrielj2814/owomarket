<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use App\Models\CentralCustomer;
use App\Models\CentralCustomerWishlist;
use App\Models\CentralOrder;
use App\Models\SupportTicket;
use Exception;

final class GetCentralCustomerDetailForAdminUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $customerId): array
    {
        $customer = CentralCustomer::with(['addresses'])->find($customerId);

        if (! $customer) {
            throw new Exception("Cliente '{$customerId}' no encontrado.", 404);
        }

        $orders = CentralOrder::where('customer_id', $customerId)
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $tickets = SupportTicket::where('user_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $totalSpentUsd = (float) CentralOrder::where('customer_id', $customerId)
            ->where('payment_status', 'paid')
            ->sum('total_usd');

        return [
            'customer' => $customer,
            'orders' => $orders,
            'tickets' => $tickets,
            'metrics' => [
                'total_orders' => count($orders),
                'total_spent_usd' => round($totalSpentUsd, 2),
                'tickets_count' => count($tickets),
            ],
        ];
    }
}
