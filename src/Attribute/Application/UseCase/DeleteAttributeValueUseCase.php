<?php

declare(strict_types=1);

namespace Src\Attribute\Application\UseCase;

use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Domain\Exceptions\AttributeValueNotFoundException;
use Src\Attribute\Domain\ValueObjects\AttributeValueId;

final class DeleteAttributeValueUseCase
{
    public function __construct(
        private readonly AttributeRepositoryInterface $repository
    ) {}

    public function execute(string $valueId): void
    {
        $attrValId = AttributeValueId::fromString($valueId);
        $value = $this->repository->findValueById($attrValId);

        if ($value === null) {
            throw new AttributeValueNotFoundException($valueId);
        }

        $this->repository->deleteValue($attrValId);
    }
}
