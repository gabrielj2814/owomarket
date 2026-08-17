<?php

declare(strict_types=1);

namespace Src\Tax\Application\Contracts;

use Src\Tax\Application\DTOs\PaginatedTaxRatesResult;
use Src\Tax\Application\DTOs\TaxRateFilterCriteria;
use Src\Tax\Domain\Entities\TaxRate;
use Src\Tax\Domain\ValueObjects\TaxRateId;

interface TaxRateRepositoryInterface
{
    public function save(TaxRate $taxRate): TaxRate;

    public function findById(TaxRateId $id): ?TaxRate;

    public function update(TaxRate $taxRate): TaxRate;

    public function delete(TaxRateId $id): void;

    public function filter(TaxRateFilterCriteria $criteria): PaginatedTaxRatesResult;

    /**
     * @return TaxRate[]
     */
    public function findApplicableRates(?string $country = null, ?string $state = null, ?string $city = null, ?string $zip = null): array;
}
