<?php

declare(strict_types=1);

namespace Src\Attribute\Application\UseCase;

use InvalidArgumentException;
use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Domain\Entities\ProductAttribute;
use Src\Attribute\Domain\Exceptions\AttributeNotFoundException;
use Src\Attribute\Domain\ValueObjects\AttributeId;
use Src\Attribute\Domain\ValueObjects\AttributeName;
use Src\Attribute\Domain\ValueObjects\AttributeSlug;
use Src\Attribute\Domain\ValueObjects\AttributeType;

final class EditAttributeUseCase
{
    public function __construct(
        private readonly AttributeRepositoryInterface $repository
    ) {}

    public function execute(
        string $id,
        string $name,
        ?string $slug = null,
        string $type = 'select',
        bool $isFilterable = false,
        bool $isVisible = true,
        int $position = 0
    ): ProductAttribute {
        $attributeId = AttributeId::fromString($id);
        $attribute = $this->repository->findById($attributeId);

        if ($attribute === null) {
            throw new AttributeNotFoundException($id);
        }

        $attributeName = AttributeName::make($name);
        $attributeSlug = AttributeSlug::fromString($slug && trim($slug) !== '' ? $slug : $name);
        $attributeType = AttributeType::fromString($type);

        $existingWithSlug = $this->repository->findBySlug($attributeSlug);
        if ($existingWithSlug !== null && $existingWithSlug->id()?->value() !== $attributeId->value()) {
            throw new InvalidArgumentException(
                sprintf('El slug "%s" ya está en uso por otro atributo.', $attributeSlug->value())
            );
        }

        $attribute->changeName($attributeName);
        $attribute->changeSlug($attributeSlug);
        $attribute->changeType($attributeType);
        $attribute->changeIsFilterable($isFilterable);
        $attribute->changeIsVisible($isVisible);
        $attribute->changePosition($position);

        return $this->repository->update($attribute);
    }
}
