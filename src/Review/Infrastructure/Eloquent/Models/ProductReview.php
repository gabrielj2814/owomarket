<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Eloquent\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Customer\Infrastructure\Eloquent\Models\Customer;
use Src\Order\Infrastructure\Eloquent\Models\Order;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Review\Domain\Entities\ProductReview as DomainProductReview;
use Src\Review\Domain\ValueObjects\Rating;
use Src\Review\Domain\ValueObjects\ReviewId;

class ProductReview extends Model
{
    use HasUuids;

    protected $table = 'product_reviews';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'response',
        'responded_at',
        'is_approved',
        'is_verified',
    ];

    protected $casts = [
        'id' => 'string',
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'is_verified' => 'boolean',
        'responded_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function toDomain(): DomainProductReview
    {
        return new DomainProductReview(
            id: ReviewId::fromString((string) $this->id),
            productId: (string) $this->product_id,
            customerId: (string) $this->customer_id,
            rating: Rating::fromInt((int) $this->rating),
            orderId: $this->order_id !== null ? (string) $this->order_id : null,
            title: $this->title !== null ? (string) $this->title : null,
            comment: $this->comment !== null ? (string) $this->comment : null,
            response: $this->response !== null ? (string) $this->response : null,
            respondedAt: $this->responded_at ? DateTimeImmutable::createFromMutable($this->responded_at) : null,
            isApproved: (bool) $this->is_approved,
            isVerified: (bool) $this->is_verified,
            createdAt: $this->created_at ? DateTimeImmutable::createFromMutable($this->created_at) : null,
            updatedAt: $this->updated_at ? DateTimeImmutable::createFromMutable($this->updated_at) : null
        );
    }
}
