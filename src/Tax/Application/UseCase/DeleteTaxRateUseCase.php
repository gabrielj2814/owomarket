<?php

declare(strict_types=1);

namespace Src\Tax\Application\UseCase;

use Src\Tax\Application\Contracts\TaxRateRepositoryInterface;
use Src\Tax\Domain\Exceptions\TaxRateNotFoundException;
use Src\Tax\Domain\ValueObjects\TaxRateId;

final class DeleteTaxRateUseCase
{
    public function __construct(
        private readonly TaxRateRepositoryInterface $repository
    ) {}

    public function execute(string $id): void
    {
        $taxRateId = TaxRateId::fromString($id);
        $taxRate = $this->repository->findById($taxRateId);

        if ($taxRate === null) {
            throw new TaxRateNotFoundException($id);
        }

        $this->repository->delete($taxRateId);
    }
}
