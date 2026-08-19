<?php

declare(strict_types=1);

namespace Src\Attribute\Infrastructure\Eloquent\Repositories;

use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Application\DTOs\AttributeFilterCriteria;
use Src\Attribute\Application\DTOs\PaginatedAttributesResult;
use Src\Attribute\Domain\Entities\ProductAttribute;
use Src\Attribute\Domain\Entities\ProductAttributeValue;
use Src\Attribute\Domain\ValueObjects\AttributeId;
use Src\Attribute\Domain\ValueObjects\AttributeName;
use Src\Attribute\Domain\ValueObjects\AttributeSlug;
use Src\Attribute\Domain\ValueObjects\AttributeType;
use Src\Attribute\Domain\ValueObjects\AttributeValueColor;
use Src\Attribute\Domain\ValueObjects\AttributeValueId;
use Src\Attribute\Domain\ValueObjects\AttributeValueImage;
use Src\Attribute\Domain\ValueObjects\AttributeValueText;
use Src\Attribute\Infrastructure\Eloquent\Models\ProductAttribute as EloquentAttribute;
use Src\Attribute\Infrastructure\Eloquent\Models\ProductAttributeValue as EloquentAttributeValue;

final class AttributeRepository implements AttributeRepositoryInterface
{
    public function save(ProductAttribute $attribute): ProductAttribute
    {
        $model = EloquentAttribute::create([
            'name' => $attribute->name()->value(),
            'slug' => $attribute->slug()->value(),
            'type' => $attribute->type()->value(),
            'is_filterable' => $attribute->isFilterable(),
            'is_visible' => $attribute->isVisible(),
            'position' => $attribute->position(),
        ]);

        return $this->toDomain($model);
    }

    public function findById(AttributeId $id): ?ProductAttribute
    {
        $model = EloquentAttribute::with('values')->find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySlug(AttributeSlug $slug): ?ProductAttribute
    {
        $model = EloquentAttribute::with('values')->where('slug', $slug->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function update(ProductAttribute $attribute): ProductAttribute
    {
        $model = EloquentAttribute::findOrFail($attribute->id()->value());

        $model->update([
            'name' => $attribute->name()->value(),
            'slug' => $attribute->slug()->value(),
            'type' => $attribute->type()->value(),
            'is_filterable' => $attribute->isFilterable(),
            'is_visible' => $attribute->isVisible(),
            'position' => $attribute->position(),
        ]);

        return $this->toDomain($model->fresh('values'));
    }

    public function delete(AttributeId $id): void
    {
        EloquentAttributeValue::where('product_attribute_id', $id->value())->delete();
        EloquentAttribute::where('id', $id->value())->delete();
    }

    public function filter(AttributeFilterCriteria $criteria): PaginatedAttributesResult
    {
        $query = EloquentAttribute::with('values');

        if ($criteria->search !== null && trim($criteria->search) !== '') {
            $search = '%'.trim($criteria->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('slug', 'like', $search);
            });
        }

        if ($criteria->type !== null && trim($criteria->type) !== '') {
            $query->where('type', $criteria->type);
        }

        if ($criteria->isFilterable !== null) {
            $query->where('is_filterable', $criteria->isFilterable);
        }

        if ($criteria->isVisible !== null) {
            $query->where('is_visible', $criteria->isVisible);
        }

        $allowedSorts = ['id', 'name', 'slug', 'type', 'position', 'created_at'];
        $sortBy = in_array($criteria->sortBy, $allowedSorts, true) ? $criteria->sortBy : 'position';
        $sortDirection = strtolower($criteria->sortDirection) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page
        );

        $items = array_map(
            fn (EloquentAttribute $model) => $this->toDomain($model),
            $paginator->items()
        );

        return new PaginatedAttributesResult(
            items: $items,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage()
        );
    }

    /**
     * @return ProductAttribute[]
     */
    public function listWithValues(): array
    {
        $models = EloquentAttribute::with(['values' => fn ($q) => $q->orderBy('position', 'asc')])
            ->where('is_visible', true)
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return $models->map(fn (EloquentAttribute $m) => $this->toDomain($m))->all();
    }

    public function saveValue(ProductAttributeValue $value): ProductAttributeValue
    {
        $model = EloquentAttributeValue::create([
            'product_attribute_id' => $value->attributeId()->value(),
            'value' => $value->value()->value(),
            'color' => $value->color()->value(),
            'image' => $value->image()->value(),
            'position' => $value->position(),
        ]);

        return $this->toValueDomain($model);
    }

    public function findValueById(AttributeValueId $id): ?ProductAttributeValue
    {
        $model = EloquentAttributeValue::find($id->value());

        return $model ? $this->toValueDomain($model) : null;
    }

    public function deleteValue(AttributeValueId $id): void
    {
        EloquentAttributeValue::where('id', $id->value())->delete();
    }

    private function toDomain(EloquentAttribute $model): ProductAttribute
    {
        $values = [];
        if ($model->relationLoaded('values') && $model->values) {
            $values = $model->values->map(fn (EloquentAttributeValue $v) => $this->toValueDomain($v))->all();
        }

        return new ProductAttribute(
            id: AttributeId::fromString((string) $model->id),
            name: AttributeName::make($model->name),
            slug: AttributeSlug::fromString($model->slug),
            type: AttributeType::fromString($model->type ?? 'select'),
            isFilterable: (bool) $model->is_filterable,
            isVisible: (bool) $model->is_visible,
            position: (int) ($model->position ?? 0),
            values: $values,
            createdAt: $model->created_at?->toISOString(),
            updatedAt: $model->updated_at?->toISOString()
        );
    }

    private function toValueDomain(EloquentAttributeValue $model): ProductAttributeValue
    {
        return new ProductAttributeValue(
            id: AttributeValueId::fromString((string) $model->id),
            attributeId: AttributeId::fromString((string) $model->product_attribute_id),
            value: AttributeValueText::make($model->value),
            color: AttributeValueColor::fromNullableString($model->color),
            image: AttributeValueImage::fromNullableString($model->image),
            position: (int) ($model->position ?? 0),
            createdAt: $model->created_at?->toISOString(),
            updatedAt: $model->updated_at?->toISOString()
        );
    }
}
