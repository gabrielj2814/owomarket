<?php

declare(strict_types=1);

namespace Src\Shipping\Application\UseCase;

use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Domain\Exceptions\ShippingRateNotFoundException;
use Src\Shipping\Domain\ValueObjects\ShippingRateId;

final class DeleteShippingRateUseCase
{
    public function __construct(
        private readonly ShippingRepositoryInterface $repository
    ) {}

    public function execute(string $rateId): void
    {
        $id = ShippingRateId::fromString($rateId);
        $rate = $this->repository->findRateById($id);

        if ($rate === null) {
            throw new ShippingRateNotFoundException($rateId);
        }

        $this->repository->deleteRate($id);
    }
}
