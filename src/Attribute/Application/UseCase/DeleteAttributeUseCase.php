<?php

declare(strict_types=1);

namespace Src\Attribute\Application\UseCase;

use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Domain\Exceptions\AttributeNotFoundException;
use Src\Attribute\Domain\ValueObjects\AttributeId;

final class DeleteAttributeUseCase
{
    public function __construct(
        private readonly AttributeRepositoryInterface $repository
    ) {}

    public function execute(string $id): void
    {
        $attributeId = AttributeId::fromString($id);
        $attribute = $this->repository->findById($attributeId);

        if ($attribute === null) {
            throw new AttributeNotFoundException($id);
        }

        $this->repository->delete($attributeId);
    }
}
