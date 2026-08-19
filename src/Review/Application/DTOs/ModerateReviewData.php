<?php

declare(strict_types=1);

namespace Src\Review\Application\DTOs;

final class ModerateReviewData
{
    public function __construct(
        public readonly string $id,
        public readonly bool $isApproved
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            isApproved: (bool) $data['is_approved']
        );
    }
}
