<?php

declare(strict_types=1);

namespace Src\Tax\Application\UseCase;

use Src\Tax\Application\Contracts\TaxRateRepositoryInterface;
use Src\Tax\Application\DTOs\CalculateTaxResult;

final class CalculateTaxUseCase
{
    public function __construct(
        private readonly TaxRateRepositoryInterface $repository
    ) {}

    public function execute(
        float $subtotal,
        ?string $country = null,
        ?string $state = null,
        ?string $city = null,
        ?string $zip = null
    ): CalculateTaxResult {
        $rates = $this->repository->findApplicableRates($country, $state, $city, $zip);

        $totalTax = 0.0;
        $appliedRates = [];

        foreach ($rates as $rate) {
            $taxAmount = $rate->calculateTax($subtotal);
            $totalTax += $taxAmount;
            $appliedRates[] = [
                'id' => $rate->id()?->value(),
                'name' => $rate->name()->value(),
                'rate' => $rate->rate()->value(),
                'tax_amount' => $taxAmount,
            ];
        }

        $totalTax = round($totalTax, 2);
        $totalWithTax = round($subtotal + $totalTax, 2);

        return new CalculateTaxResult(
            subtotal: round($subtotal, 2),
            totalTax: $totalTax,
            totalWithTax: $totalWithTax,
            appliedRates: $appliedRates
        );
    }
}
