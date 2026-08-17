<?php

declare(strict_types=1);

namespace Src\Attribute\Application\Contracts;

use Src\Attribute\Application\DTOs\AttributeFilterCriteria;
use Src\Attribute\Application\DTOs\PaginatedAttributesResult;
use Src\Attribute\Domain\Entities\ProductAttribute;
use Src\Attribute\Domain\Entities\ProductAttributeValue;
use Src\Attribute\Domain\ValueObjects\AttributeId;
use Src\Attribute\Domain\ValueObjects\AttributeSlug;
use Src\Attribute\Domain\ValueObjects\AttributeValueId;

interface AttributeRepositoryInterface
{
    public function save(ProductAttribute $attribute): ProductAttribute;

    public function findById(AttributeId $id): ?ProductAttribute;

    public function findBySlug(AttributeSlug $slug): ?ProductAttribute;

    public function update(ProductAttribute $attribute): ProductAttribute;

    public function delete(AttributeId $id): void;

    public function filter(AttributeFilterCriteria $criteria): PaginatedAttributesResult;

    /**
     * @return ProductAttribute[]
     */
    public function listWithValues(): array;

    public function saveValue(ProductAttributeValue $value): ProductAttributeValue;

    public function findValueById(AttributeValueId $id): ?ProductAttributeValue;

    public function deleteValue(AttributeValueId $id): void;
}
