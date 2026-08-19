<?php

declare(strict_types=1);

namespace Src\Brand\Application\UseCase;

use Src\Brand\Application\Contracts\BrandRepositoryInterface;
use Src\Brand\Domain\Entities\Brand;

final class ListAllActiveBrandsUseCase
{
    public function __construct(
        private readonly BrandRepositoryInterface $repository
    ) {}

    /**
     * @return Brand[]
     */
    public function execute(): array
    {
        return $this->repository->listAllActive();
    }
}
