<?php

declare(strict_types=1);

namespace Src\Tax\Application\DTOs;

final class CalculateTaxResult
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $totalTax,
        public readonly float $totalWithTax,
        public readonly array $appliedRates
    ) {}

    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'total_tax' => $this->totalTax,
            'total_with_tax' => $this->totalWithTax,
            'applied_rates' => $this->appliedRates,
        ];
    }
}
