<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralMarketplace\Application\UseCases\CreateUnifiedCentralOrderUseCase;
use Src\Shared\Helper\ApiResponse;

final class CreateUnifiedCentralOrderPOSTController
{
    public function __construct(
        private readonly CreateUnifiedCentralOrderUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'customer' => 'required|array',
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'nullable|string|max:50',
            'shipping_address' => 'required|array',
            'shipping_address.address' => 'required|string',
            'shipping_address.city' => 'required|string',
            'payment_method' => 'required|string',
            'payment_details' => 'nullable|array',
            // Hallazgo C2: clave que el navegador genera una vez por intento de
            // compra. Si el checkout se reenvía con la misma clave, se devuelve
            // el pedido ya creado en lugar de duplicarlo.
            'idempotency_key' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.tenant_id' => 'required|string',
            'items.*.product_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            // 'price' y 'product_name' ya no se validan ni se usan: se
            // resuelven contra el catálogo central (hallazgo B1).
        ]);

        try {
            $centralOrder = $this->useCase->execute($request->all());

            return ApiResponse::success(
                data: [
                    'order_id' => $centralOrder->id,
                    'order_number' => $centralOrder->order_number,
                    'total' => (float) $centralOrder->total,
                    'redirect_url' => "/central/order/{$centralOrder->id}/confirmation",
                ],
                message: '¡Pedido consolidado multi-tienda creado exitosamente!',
                code: 201
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
