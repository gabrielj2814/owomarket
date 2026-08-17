<?php

declare(strict_types=1);

namespace Src\Attribute\Application\UseCase;

use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Domain\Entities\ProductAttribute;
use Src\Attribute\Domain\Exceptions\AttributeNotFoundException;
use Src\Attribute\Domain\ValueObjects\AttributeId;

final class ConsultAttributeByIdUseCase
{
    public function __construct(
        private readonly AttributeRepositoryInterface $repository
    ) {}

    public function execute(string $id): ProductAttribute
    {
        $attribute = $this->repository->findById(AttributeId::fromString($id));

        if ($attribute === null) {
            throw new AttributeNotFoundException($id);
        }

        return $attribute;
    }
}
