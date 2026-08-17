<?php

declare(strict_types=1);

namespace Src\Shipping\Application\UseCase;

use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Application\DTOs\CalculateShippingResult;

final class CalculateShippingOptionsUseCase
{
    public function __construct(
        private readonly ShippingRepositoryInterface $repository
    ) {}

    public function execute(
        float $orderValue,
        float $totalWeight = 0.0,
        ?string $country = null,
        ?string $state = null,
        ?string $postalCode = null
    ): CalculateShippingResult {
        $zones = $this->repository->findMatchingZones($country, $state, $postalCode);

        $options = [];
        $cheapestOption = null;
        $cheapestCost = PHP_FLOAT_MAX;

        foreach ($zones as $zone) {
            foreach ($zone->rates() as $rate) {
                if ($rate->appliesTo($orderValue, $totalWeight)) {
                    $cost = $rate->calculateCost();
                    $option = [
                        'zone_id' => $zone->id()?->value(),
                        'zone_name' => $zone->name()->value(),
                        'rate_id' => $rate->id()?->value(),
                        'name' => $rate->name()->value(),
                        'type' => $rate->type()->value(),
                        'cost' => $cost,
                    ];
                    $options[] = $option;

                    if ($cost < $cheapestCost) {
                        $cheapestCost = $cost;
                        $cheapestOption = $option;
                    }
                }
            }
        }

        return new CalculateShippingResult(
            options: $options,
            recommendedOption: $cheapestOption
        );
    }
}
