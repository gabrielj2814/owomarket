<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Exception;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;

final class GetCustomerOrderDetailUseCase
{
    public function execute(string $customerId, string $orderId): CentralOrder
    {
        $order = CentralOrder::with('items')
            ->where('id', $orderId)
            ->where(function ($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->first();

        if (! $order) {
            throw new Exception('Pedido no encontrado o no pertenece a este usuario.', 404);
        }

        return $order;
    }
}
