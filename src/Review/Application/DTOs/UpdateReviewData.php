<?php

declare(strict_types=1);

namespace Src\Review\Application\DTOs;

final class UpdateReviewData
{
    public function __construct(
        public readonly string $id,
        public readonly int $rating,
        public readonly ?string $title = null,
        public readonly ?string $comment = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            rating: (int) $data['rating'],
            title: isset($data['title']) && $data['title'] !== '' ? (string) $data['title'] : null,
            comment: isset($data['comment']) && $data['comment'] !== '' ? (string) $data['comment'] : null
        );
    }
}
