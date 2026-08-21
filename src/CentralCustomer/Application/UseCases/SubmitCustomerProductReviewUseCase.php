<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Review\Infrastructure\Eloquent\Models\ProductReview;

final class SubmitCustomerProductReviewUseCase
{
    /**
     * @param  array{order_id: string, product_id: string, rating: int, title?: string|null, comment: string}  $data
     * @return array{success: bool, message: string}
     */
    public function execute(string $customerId, array $data): array
    {
        $rating = (int) $data['rating'];
        if ($rating < 1 || $rating > 5) {
            throw new Exception('La valoración debe ser entre 1 y 5 estrellas.', 422);
        }

        $order = CentralOrder::with('items')
            ->where('id', $data['order_id'])
            ->where('customer_id', $customerId)
            ->first();

        if (! $order) {
            throw new Exception('El pedido no fue encontrado o no pertenece a tu cuenta.', 404);
        }

        $item = $order->items->firstWhere('product_id', $data['product_id']);
        if (! $item) {
            throw new Exception('El producto a calificar no pertenece a este pedido.', 422);
        }

        if (Schema::hasTable('product_reviews')) {
            ProductReview::updateOrCreate(
                [
                    'product_id' => $item->product_id,
                    'customer_id' => $customerId,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'order_id' => $order->id,
                    'rating' => $rating,
                    'title' => isset($data['title']) ? trim($data['title']) : null,
                    'comment' => trim($data['comment']),
                    'is_approved' => true,
                    'is_verified' => true,
                ]
            );
        }

        return [
            'success' => true,
            'message' => '¡Gracias por tu opinión! Tu reseña ha sido publicada.',
        ];
    }
}
