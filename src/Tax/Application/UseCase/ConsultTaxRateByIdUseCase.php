<?php

declare(strict_types=1);

namespace Src\Tax\Application\UseCase;

use Src\Tax\Application\Contracts\TaxRateRepositoryInterface;
use Src\Tax\Domain\Entities\TaxRate;
use Src\Tax\Domain\Exceptions\TaxRateNotFoundException;
use Src\Tax\Domain\ValueObjects\TaxRateId;

final class ConsultTaxRateByIdUseCase
{
    public function __construct(
        private readonly TaxRateRepositoryInterface $repository
    ) {}

    public function execute(string $id): TaxRate
    {
        $taxRate = $this->repository->findById(TaxRateId::fromString($id));

        if ($taxRate === null) {
            throw new TaxRateNotFoundException($id);
        }

        return $taxRate;
    }
}
