<?php

declare(strict_types=1);

namespace Src\Review\Domain\Entities;

use DateTimeImmutable;
use Src\Review\Domain\ValueObjects\Rating;
use Src\Review\Domain\ValueObjects\ReviewId;

final class ProductReview
{
    private ReviewId $id;

    private string $productId;

    private string $customerId;

    private ?string $orderId;

    private Rating $rating;

    private ?string $title;

    private ?string $comment;

    private ?string $response;

    private ?DateTimeImmutable $respondedAt;

    private bool $isApproved;

    private bool $isVerified;

    private ?DateTimeImmutable $createdAt;

    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        ReviewId $id,
        string $productId,
        string $customerId,
        Rating $rating,
        ?string $orderId = null,
        ?string $title = null,
        ?string $comment = null,
        ?string $response = null,
        ?DateTimeImmutable $respondedAt = null,
        bool $isApproved = false,
        bool $isVerified = false,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->productId = $productId;
        $this->customerId = $customerId;
        $this->rating = $rating;
        $this->orderId = $orderId;
        $this->title = $title;
        $this->comment = $comment;
        $this->response = $response;
        $this->respondedAt = $respondedAt;
        $this->isApproved = $isApproved;
        $this->isVerified = $isVerified;
        $this->createdAt = $createdAt ?? new DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable;
    }

    public static function create(
        string $productId,
        string $customerId,
        Rating $rating,
        ?string $orderId = null,
        ?string $title = null,
        ?string $comment = null,
        bool $isApproved = false,
        bool $isVerified = false,
        ?ReviewId $id = null
    ): self {
        $now = new DateTimeImmutable;

        return new self(
            id: $id ?? ReviewId::random(),
            productId: $productId,
            customerId: $customerId,
            rating: $rating,
            orderId: $orderId,
            title: $title,
            comment: $comment,
            response: null,
            respondedAt: null,
            isApproved: $isApproved,
            isVerified: $isVerified,
            createdAt: $now,
            updatedAt: $now
        );
    }

    public function approve(): void
    {
        $this->isApproved = true;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function reject(): void
    {
        $this->isApproved = false;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function respond(string $response, ?DateTimeImmutable $respondedAt = null): void
    {
        $this->response = trim($response);
        $this->respondedAt = $respondedAt ?? new DateTimeImmutable;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function clearResponse(): void
    {
        $this->response = null;
        $this->respondedAt = null;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function updateContent(Rating $rating, ?string $title = null, ?string $comment = null): void
    {
        $this->rating = $rating;
        $this->title = $title !== null ? trim($title) : null;
        $this->comment = $comment !== null ? trim($comment) : null;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function markAsVerified(bool $isVerified = true): void
    {
        $this->isVerified = $isVerified;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function id(): ReviewId
    {
        return $this->id;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function orderId(): ?string
    {
        return $this->orderId;
    }

    public function rating(): Rating
    {
        return $this->rating;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function response(): ?string
    {
        return $this->response;
    }

    public function respondedAt(): ?DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasResponse(): bool
    {
        return ! empty($this->response);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'product_id' => $this->productId,
            'customer_id' => $this->customerId,
            'order_id' => $this->orderId,
            'rating' => $this->rating->value(),
            'title' => $this->title,
            'comment' => $this->comment,
            'response' => $this->response,
            'responded_at' => $this->respondedAt?->format('Y-m-d H:i:s'),
            'is_approved' => $this->isApproved,
            'is_verified' => $this->isVerified,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
