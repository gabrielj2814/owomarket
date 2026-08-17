<?php

declare(strict_types=1);

namespace Src\Tax\Application\UseCase;

use Src\Tax\Application\Contracts\TaxRateRepositoryInterface;
use Src\Tax\Domain\Entities\TaxRate;
use Src\Tax\Domain\Exceptions\TaxRateNotFoundException;
use Src\Tax\Domain\ValueObjects\TaxRateId;
use Src\Tax\Domain\ValueObjects\TaxRateName;
use Src\Tax\Domain\ValueObjects\TaxRatePercentage;
use Src\Tax\Domain\ValueObjects\TaxRatePriority;

final class EditTaxRateUseCase
{
    public function __construct(
        private readonly TaxRateRepositoryInterface $repository
    ) {}

    public function execute(
        string $id,
        string $name,
        float $rate,
        ?string $country = null,
        ?string $state = null,
        ?string $city = null,
        ?string $zip = null,
        int $priority = 0,
        bool $isActive = true
    ): TaxRate {
        $taxRateId = TaxRateId::fromString($id);
        $taxRate = $this->repository->findById($taxRateId);

        if ($taxRate === null) {
            throw new TaxRateNotFoundException($id);
        }

        $taxRate->changeName(TaxRateName::make($name));
        $taxRate->changeRate(TaxRatePercentage::create($rate));
        $taxRate->changeLocation($country, $state, $city, $zip);
        $taxRate->changePriority(TaxRatePriority::fromInt($priority));

        if ($isActive) {
            $taxRate->activate();
        } else {
            $taxRate->deactivate();
        }

        return $this->repository->update($taxRate);
    }
}
