<?php

declare(strict_types=1);

namespace Src\Attribute\Application\DTOs;

final class AttributeValueData
{
    public function __construct(
        public readonly string $value,
        public readonly ?string $color = null,
        public readonly ?string $image = null,
        public readonly int $position = 0
    ) {}
}
