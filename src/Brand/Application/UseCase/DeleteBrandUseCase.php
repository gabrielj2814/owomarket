<?php

declare(strict_types=1);

namespace Src\Brand\Application\UseCase;

use Src\Brand\Application\Contracts\BrandRepositoryInterface;
use Src\Brand\Domain\Exceptions\BrandNotFoundException;
use Src\Brand\Domain\ValueObjects\BrandId;

final class DeleteBrandUseCase
{
    public function __construct(
        private readonly BrandRepositoryInterface $repository
    ) {}

    public function execute(int $id): void
    {
        $brandId = new BrandId($id);
        $brand = $this->repository->findById($brandId);

        if ($brand === null) {
            throw new BrandNotFoundException($id);
        }

        $this->repository->delete($brandId);
    }
}
