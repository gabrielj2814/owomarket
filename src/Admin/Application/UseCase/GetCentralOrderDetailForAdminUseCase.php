<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;

final class GetCentralOrderDetailForAdminUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $orderId): array
    {
        $order = CentralOrder::with(['items', 'customer', 'commissions'])->find($orderId);

        if (! $order) {
            throw new Exception("Orden '{$orderId}' no encontrada.", 404);
        }

        return [
            'order' => $order,
        ];
    }
}
