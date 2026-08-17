<?php

declare(strict_types=1);

namespace Src\Category\Infrastructure\Eloquent\Repositories;

use App\Models\Category as EloquentCategory;
use Src\Category\Application\Contracts\CategoryRepositoryInterface;
use Src\Category\Application\DTOs\CategoryFilterCriteria;
use Src\Category\Application\DTOs\PaginatedCategoriesResult;
use Src\Category\Domain\Entities\Category;
use Src\Category\Domain\ValueObjects\CategoryDescription;
use Src\Category\Domain\ValueObjects\CategoryId;
use Src\Category\Domain\ValueObjects\CategoryName;
use Src\Category\Domain\ValueObjects\CategorySlug;
use Src\Category\Domain\ValueObjects\CategoryStatus;
use Src\Category\Domain\ValueObjects\ParentCategoryId;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function create(Category $category): Category
    {
        $model = new EloquentCategory;
        $model->name = $category->getName()->value();
        $model->slug = $category->getSlug()->value();
        $model->description = $category->getDescription()->value();
        $model->image = $category->getImage();
        $model->parent_id = $category->getParentId()->value();
        $model->is_active = $category->getIsActive()->value();
        $model->position = $category->getPosition();
        $model->save();

        return $this->toDomain($model);
    }

    public function edit(Category $category): Category
    {
        $model = EloquentCategory::query()->where('id', $category->getId()->value())->first();

        if ($model === null) {
            throw new \RuntimeException('Category model not found for editing.');
        }

        $model->name = $category->getName()->value();
        $model->slug = $category->getSlug()->value();
        $model->description = $category->getDescription()->value();
        $model->image = $category->getImage();
        $model->parent_id = $category->getParentId()->value();
        $model->is_active = $category->getIsActive()->value();
        $model->position = $category->getPosition();
        $model->save();

        return $this->toDomain($model);
    }

    public function findById(CategoryId $id): ?Category
    {
        if ($id->isNull()) {
            return null;
        }

        $model = EloquentCategory::query()->where('id', $id->value())->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function findBySlug(CategorySlug $slug): ?Category
    {
        $model = EloquentCategory::query()->where('slug', $slug->value())->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function delete(CategoryId $id): void
    {
        if ($id->isNull()) {
            return;
        }

        EloquentCategory::query()->where('id', $id->value())->delete();
    }

    public function filter(CategoryFilterCriteria $criteria): PaginatedCategoriesResult
    {
        $query = EloquentCategory::query();

        if ($criteria->search !== null && trim($criteria->search) !== '') {
            $search = '%'.trim($criteria->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('slug', 'like', $search);
            });
        }

        if ($criteria->isActive !== null) {
            $query->where('is_active', $criteria->isActive);
        }

        if ($criteria->parentId !== null) {
            $query->where('parent_id', $criteria->parentId);
        }

        if ($criteria->fechaDesdeUTC !== null && trim($criteria->fechaDesdeUTC) !== '') {
            $query->where('created_at', '>=', $criteria->fechaDesdeUTC);
        }

        if ($criteria->fechaHastaUTC !== null && trim($criteria->fechaHastaUTC) !== '') {
            $query->where('created_at', '<=', $criteria->fechaHastaUTC);
        }

        $query->orderBy('position', 'asc')->orderBy('name', 'asc');

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page
        );

        $domainItems = array_map(
            fn (EloquentCategory $model) => $this->toDomain($model),
            $paginator->items()
        );

        return new PaginatedCategoriesResult(
            items: $domainItems,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage()
        );
    }

    /**
     * @return Category[]
     */
    public function getTree(): array
    {
        $rootCategories = EloquentCategory::query()
            ->with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return array_map(fn (EloquentCategory $model) => $this->toDomainWithChildren($model), $rootCategories->all());
    }

    private function toDomain(EloquentCategory $model): Category
    {
        return Category::reconstitute(
            id: CategoryId::fromInt($model->id),
            name: CategoryName::make($model->name),
            slug: CategorySlug::make($model->slug),
            description: CategoryDescription::make($model->description),
            image: $model->image,
            parentId: ParentCategoryId::fromNullableInt($model->parent_id),
            isActive: CategoryStatus::fromBool((bool) $model->is_active),
            position: (int) ($model->position ?? 0),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
            children: []
        );
    }

    private function toDomainWithChildren(EloquentCategory $model): Category
    {
        $children = [];
        if ($model->relationLoaded('children') && $model->children !== null) {
            $children = array_map(
                fn (EloquentCategory $child) => $this->toDomainWithChildren($child),
                $model->children->all()
            );
        }

        return Category::reconstitute(
            id: CategoryId::fromInt($model->id),
            name: CategoryName::make($model->name),
            slug: CategorySlug::make($model->slug),
            description: CategoryDescription::make($model->description),
            image: $model->image,
            parentId: ParentCategoryId::fromNullableInt($model->parent_id),
            isActive: CategoryStatus::fromBool((bool) $model->is_active),
            position: (int) ($model->position ?? 0),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
            children: $children
        );
    }
}
