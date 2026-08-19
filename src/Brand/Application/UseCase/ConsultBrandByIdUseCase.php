<?php

declare(strict_types=1);

namespace Src\Brand\Application\UseCase;

use Src\Brand\Application\Contracts\BrandRepositoryInterface;
use Src\Brand\Domain\Entities\Brand;
use Src\Brand\Domain\Exceptions\BrandNotFoundException;
use Src\Brand\Domain\ValueObjects\BrandId;

final class ConsultBrandByIdUseCase
{
    public function __construct(
        private readonly BrandRepositoryInterface $repository
    ) {}

    public function execute(int $id): Brand
    {
        $brand = $this->repository->findById(new BrandId($id));

        if ($brand === null) {
            throw new BrandNotFoundException($id);
        }

        return $brand;
    }
}
