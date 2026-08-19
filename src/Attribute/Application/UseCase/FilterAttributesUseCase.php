<?php

declare(strict_types=1);

namespace Src\Attribute\Application\UseCase;

use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Application\DTOs\AttributeFilterCriteria;
use Src\Attribute\Application\DTOs\PaginatedAttributesResult;

final class FilterAttributesUseCase
{
    public function __construct(
        private readonly AttributeRepositoryInterface $repository
    ) {}

    public function execute(AttributeFilterCriteria $criteria): PaginatedAttributesResult
    {
        return $this->repository->filter($criteria);
    }
}
