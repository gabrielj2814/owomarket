<?php

declare(strict_types=1);

namespace Src\Review\Application\DTOs;

final class RespondReviewData
{
    public function __construct(
        public readonly string $id,
        public readonly string $response
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            response: (string) $data['response']
        );
    }
}
