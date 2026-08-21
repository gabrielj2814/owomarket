<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Illuminate\Support\Facades\Schema;
use Src\Review\Infrastructure\Eloquent\Models\ProductReview;

final class ListCustomerPurchasedProductsForReviewUseCase
{
    /**
     * @return array{pending: array<int, mixed>, reviewed: array<int, mixed>}
     */
    public function execute(string $customerId, ?string $customerEmail = null): array
    {
        $orders = CentralOrder::with('items')
            ->where(function ($q) use ($customerId, $customerEmail) {
                $q->where('customer_id', $customerId);
                if ($customerEmail) {
                    $q->orWhere('customer_email', strtolower(trim($customerEmail)));
                }
            })
            ->whereIn('payment_status', ['paid', 'completed'])
            ->get();

        $existingReviews = collect();
        if (Schema::hasTable('product_reviews')) {
            try {
                $existingReviews = ProductReview::where('customer_id', $customerId)->get();
            } catch (\Throwable) {
                // Ignore if in central table context
            }
        }

        $pending = [];
        $reviewed = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $review = $existingReviews->firstWhere('product_id', $item->product_id);
                if ($review) {
                    $reviewed[] = [
                        'review_id' => $review->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'tenant_id' => $item->tenant_id,
                        'rating' => $review->rating,
                        'title' => $review->title,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at?->format('d/m/Y'),
                    ];
                } else {
                    $pending[] = [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'tenant_id' => $item->tenant_id,
                        'price' => (float) $item->price,
                        'purchased_at' => $order->created_at?->format('d/m/Y'),
                    ];
                }
            }
        }

        return [
            'pending' => $pending,
            'reviewed' => $reviewed,
        ];
    }
}
