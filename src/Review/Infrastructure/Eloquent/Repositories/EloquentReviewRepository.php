<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Eloquent\Repositories;

use Src\Review\Application\DTOs\FilterReviewsCriteria;
use Src\Review\Application\DTOs\PaginatedReviewResult;
use Src\Review\Application\DTOs\ProductRatingSummaryData;
use Src\Review\Application\Repositories\ReviewRepositoryInterface;
use Src\Review\Domain\Entities\ProductReview as DomainProductReview;
use Src\Review\Domain\ValueObjects\ReviewId;
use Src\Review\Infrastructure\Eloquent\Models\ProductReview as EloquentProductReview;

final class EloquentReviewRepository implements ReviewRepositoryInterface
{
    public function save(DomainProductReview $review): void
    {
        EloquentProductReview::updateOrCreate(
            ['id' => $review->id()->value()],
            [
                'product_id' => $review->productId(),
                'customer_id' => $review->customerId(),
                'order_id' => $review->orderId(),
                'rating' => $review->rating()->value(),
                'title' => $review->title(),
                'comment' => $review->comment(),
                'response' => $review->response(),
                'responded_at' => $review->respondedAt()?->format('Y-m-d H:i:s'),
                'is_approved' => $review->isApproved(),
                'is_verified' => $review->isVerified(),
            ]
        );
    }

    public function findById(ReviewId $id): ?DomainProductReview
    {
        $model = EloquentProductReview::find($id->value());

        return $model?->toDomain();
    }

    public function findByCustomerAndProduct(string $customerId, string $productId): ?DomainProductReview
    {
        $model = EloquentProductReview::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();

        return $model?->toDomain();
    }

    /**
     * @return array<DomainProductReview>
     */
    public function findByProductId(string $productId, bool $onlyApproved = true): array
    {
        $query = EloquentProductReview::where('product_id', $productId);

        if ($onlyApproved) {
            $query->where('is_approved', true);
        }

        return $query->latest()
            ->get()
            ->map(fn (EloquentProductReview $model) => $model->toDomain())
            ->all();
    }

    public function filter(FilterReviewsCriteria $criteria): PaginatedReviewResult
    {
        $query = EloquentProductReview::query()->with(['product', 'customer']);

        if ($criteria->search !== null && $criteria->search !== '') {
            $term = '%'.$criteria->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('comment', 'like', $term)
                    ->orWhere('response', 'like', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term));
            });
        }

        if ($criteria->productId !== null && $criteria->productId !== '') {
            $query->where('product_id', $criteria->productId);
        }

        if ($criteria->customerId !== null && $criteria->customerId !== '') {
            $query->where('customer_id', $criteria->customerId);
        }

        if ($criteria->rating !== null) {
            $query->where('rating', $criteria->rating);
        }

        if ($criteria->isApproved !== null) {
            $query->where('is_approved', $criteria->isApproved);
        }

        if ($criteria->isVerified !== null) {
            $query->where('is_verified', $criteria->isVerified);
        }

        if ($criteria->hasResponse !== null) {
            if ($criteria->hasResponse) {
                $query->whereNotNull('response')->where('response', '!=', '');
            } else {
                $query->where(function ($q) {
                    $q->whereNull('response')->orWhere('response', '');
                });
            }
        }

        $allowedSorts = ['rating', 'created_at', 'is_approved', 'is_verified'];
        $sortBy = in_array($criteria->sortBy, $allowedSorts, true) ? $criteria->sortBy : 'created_at';
        $sortDirection = $criteria->sortDirection === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        $paginator = $query->paginate($criteria->perPage, ['*'], 'page', $criteria->page);

        $items = array_map(
            fn (EloquentProductReview $model) => $model->toDomain(),
            $paginator->items()
        );

        return new PaginatedReviewResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage()
        );
    }

    public function getRatingSummary(?string $productId = null): ProductRatingSummaryData
    {
        $query = EloquentProductReview::where('is_approved', true);

        if ($productId !== null && $productId !== '') {
            $query->where('product_id', $productId);
        }

        $totalReviews = (int) $query->count();
        $averageRating = $totalReviews > 0 ? (float) round((float) $query->avg('rating'), 2) : 0.0;

        $starCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $countsByRating = (clone $query)
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->all();

        foreach ($countsByRating as $star => $count) {
            $starInt = (int) $star;
            if (isset($starCounts[$starInt])) {
                $starCounts[$starInt] = (int) $count;
            }
        }

        return new ProductRatingSummaryData(
            productId: $productId,
            totalReviews: $totalReviews,
            averageRating: $averageRating,
            starBreakdown: $starCounts
        );
    }

    public function delete(ReviewId $id): void
    {
        EloquentProductReview::where('id', $id->value())->delete();
    }
}
