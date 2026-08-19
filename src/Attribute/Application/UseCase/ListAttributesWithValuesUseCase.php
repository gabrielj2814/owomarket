<?php

declare(strict_types=1);

namespace Src\Attribute\Application\UseCase;

use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Domain\Entities\ProductAttribute;

final class ListAttributesWithValuesUseCase
{
    public function __construct(
        private readonly AttributeRepositoryInterface $repository
    ) {}

    /**
     * @return ProductAttribute[]
     */
    public function execute(): array
    {
        return $this->repository->listWithValues();
    }
}
