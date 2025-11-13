<?php

namespace Src\Shared\ValuesObjects;

use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\SoftDeleteAt;
use Src\Shared\ValuesObjects\UpdatedAt;

final class Timestamps
{
    private CreatedAt $createdAt;
    private UpdatedAt $updatedAt;
    private SoftDeleteAt $deletedAt;

    private function __construct(
        ?CreatedAt $createdAt = null,
        ?UpdatedAt $updatedAt = null,
        ?SoftDeleteAt $deletedAt = null
    ) {
        $this->createdAt = $createdAt ?? CreatedAt::now();
        $this->updatedAt = $updatedAt ?? new UpdatedAt();
        $this->deletedAt = $deletedAt ?? new SoftDeleteAt();
    }

    public static function make(
        ?CreatedAt $createdAt = null,
        ?UpdatedAt $updatedAt = null,
        ?SoftDeleteAt $deletedAt = null
    ):self{
        return new self(
            $createdAt,
            $updatedAt,
            $deletedAt
        );
    }

    public static function create(): self
    {
        return new self();
    }

    public static function fromStrings(
        ?string $createdAt,
        ?string $updatedAt,
        ?string $deletedAt = null
    ): self {
        $createdAtVO = $createdAt ? CreatedAt::fromString($createdAt) : null;
        $updatedAtVO = $updatedAt ? UpdatedAt::fromString($updatedAt) : null;
        $deletedAtVO = $deletedAt ? SoftDeleteAt::fromString($deletedAt) : null;

        return new self($createdAtVO, $updatedAtVO, $deletedAtVO);
    }

    public function touch(): self
    {
        return new self(
            $this->createdAt,
            UpdatedAt::now(),
            $this->deletedAt
        );
    }

    public function softDelete(): self
    {
        return new self(
            $this->createdAt,
            UpdatedAt::now(),
            SoftDeleteAt::now()
        );
    }

    public function restore(): self
    {
        return new self(
            $this->createdAt,
            UpdatedAt::now(),
            new SoftDeleteAt() // null
        );
    }

    public function createdAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function updatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }

    public function deletedAt(): SoftDeleteAt
    {
        return $this->deletedAt;
    }

    public function toArray(): array
    {
        return [
            'created_at' => $this->createdAt->toString(),
            'updated_at' => $this->updatedAt->toString(),
            'deleted_at' => $this->deletedAt->toString(),
        ];
    }

    public function equals(self $other): bool
    {
        return $this->createdAt->equals($other->createdAt)
            && $this->updatedAt->equals($other->updatedAt)
            && $this->deletedAt->equals($other->deletedAt);
    }

    public function wasCreatedRecently(int $minutes = 5): bool
    {
        return $this->createdAt->isNewerThan($minutes);
    }

    public function wasUpdatedRecently(int $minutes = 5): bool
    {
        return $this->updatedAt->wasRecentlyUpdated($minutes);
    }

    public function wasDeletedRecently(int $minutes = 5): bool
    {
        return $this->deletedAt->wasDeletedRecently($minutes);
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt->isDeleted();
    }
}
