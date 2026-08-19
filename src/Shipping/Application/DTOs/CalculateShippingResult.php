<?php

declare(strict_types=1);

namespace Src\Shipping\Application\DTOs;

final class CalculateShippingResult
{
    public function __construct(
        public readonly array $options,
        public readonly ?array $recommendedOption = null
    ) {}

    public function toArray(): array
    {
        return [
            'options' => $this->options,
            'recommended_option' => $this->recommendedOption,
        ];
    }
}
