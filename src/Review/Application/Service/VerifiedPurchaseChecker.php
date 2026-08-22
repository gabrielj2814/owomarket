<?php

declare(strict_types=1);

namespace Src\Review\Application\Service;

use Src\Order\Infrastructure\Eloquent\Models\Order;

/**
 * Decide si una reseña puede marcarse como "compra verificada" (hallazgo B2).
 *
 * Antes, `CreateProductReviewUseCase` hacía:
 *
 *     isVerified: $data->isVerified || ! empty($data->orderId),
 *
 * con `is_verified` llegando del cuerpo de la petición y `order_id` validado
 * únicamente con `exists:orders,id`. Es decir: bastaba enviar
 * `"is_verified": true`, o el id de CUALQUIER pedido de CUALQUIER cliente,
 * para que la reseña luciera el distintivo de compra verificada.
 *
 * Ahora la insignia sólo se concede si el pedido existe, pertenece a quien
 * reseña y contiene el producto reseñado.
 */
/*
 * Sin `final`: los tests la sustituyen por un doble de Mockery. Es la regla del
 * proyecto para los colaboradores que se doblan en tests (ver `reglas.md`), no una
 * excepcion.
 */
class VerifiedPurchaseChecker
{
    public function isVerifiedPurchase(?string $orderId, string $customerId, string $productId): bool
    {
        if ($orderId === null || trim($orderId) === '' || trim($customerId) === '' || trim($productId) === '') {
            return false;
        }

        return Order::where('id', $orderId)
            ->where('customer_id', $customerId)
            ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
            ->exists();
    }
}
