<?php

declare(strict_types=1);

namespace Src\Tax\Application\UseCase;

use Src\Tax\Application\Contracts\TaxRateRepositoryInterface;
use Src\Tax\Domain\Entities\TaxRate;
use Src\Tax\Domain\ValueObjects\TaxRateName;
use Src\Tax\Domain\ValueObjects\TaxRatePercentage;
use Src\Tax\Domain\ValueObjects\TaxRatePriority;
use Src\Tax\Domain\ValueObjects\TaxRateStatus;

final class CreateTaxRateUseCase
{
    public function __construct(
        private readonly TaxRateRepositoryInterface $repository
    ) {}

    public function execute(
        string $name,
        float $rate,
        ?string $country = null,
        ?string $state = null,
        ?string $city = null,
        ?string $zip = null,
        int $priority = 0,
        bool $isActive = true
    ): TaxRate {
        $taxRate = TaxRate::create(
            name: TaxRateName::make($name),
            rate: TaxRatePercentage::create($rate),
            country: $country,
            state: $state,
            city: $city,
            zip: $zip,
            priority: TaxRatePriority::fromInt($priority),
            isActive: TaxRateStatus::fromBool($isActive)
        );

        return $this->repository->save($taxRate);
    }
}
