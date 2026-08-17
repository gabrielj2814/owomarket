<?php

declare(strict_types=1);

namespace Src\Brand\Infrastructure\Eloquent\Repositories;

use App\Models\Brand as EloquentBrand;
use Src\Brand\Application\Contracts\BrandRepositoryInterface;
use Src\Brand\Application\DTOs\BrandFilterCriteria;
use Src\Brand\Application\DTOs\PaginatedBrandsResult;
use Src\Brand\Domain\Entities\Brand;
use Src\Brand\Domain\ValueObjects\BrandDescription;
use Src\Brand\Domain\ValueObjects\BrandId;
use Src\Brand\Domain\ValueObjects\BrandLogo;
use Src\Brand\Domain\ValueObjects\BrandName;
use Src\Brand\Domain\ValueObjects\BrandSlug;
use Src\Brand\Domain\ValueObjects\BrandStatus;

final class BrandRepository implements BrandRepositoryInterface
{
    public function save(Brand $brand): Brand
    {
        $model = EloquentBrand::create([
            'name' => $brand->name()->value(),
            'slug' => $brand->slug()->value(),
            'description' => $brand->description()->value(),
            'logo' => $brand->logo()->value(),
            'is_active' => $brand->isActive()->value(),
            'position' => $brand->position(),
        ]);

        return $this->toDomain($model);
    }

    public function findById(BrandId $id): ?Brand
    {
        $model = EloquentBrand::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySlug(BrandSlug $slug): ?Brand
    {
        $model = EloquentBrand::where('slug', $slug->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function update(Brand $brand): Brand
    {
        $model = EloquentBrand::findOrFail($brand->id()->value());

        $model->update([
            'name' => $brand->name()->value(),
            'slug' => $brand->slug()->value(),
            'description' => $brand->description()->value(),
            'logo' => $brand->logo()->value(),
            'is_active' => $brand->isActive()->value(),
            'position' => $brand->position(),
        ]);

        return $this->toDomain($model->fresh());
    }

    public function delete(BrandId $id): void
    {
        EloquentBrand::where('id', $id->value())->delete();
    }

    public function filter(BrandFilterCriteria $criteria): PaginatedBrandsResult
    {
        $query = EloquentBrand::query();

        if ($criteria->search !== null && trim($criteria->search) !== '') {
            $search = '%'.trim($criteria->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('slug', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        if ($criteria->isActive !== null) {
            $query->where('is_active', $criteria->isActive);
        }

        $allowedSorts = ['id', 'name', 'slug', 'position', 'is_active', 'created_at'];
        $sortBy = in_array($criteria->sortBy, $allowedSorts, true) ? $criteria->sortBy : 'id';
        $sortDirection = strtolower($criteria->sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page
        );

        $items = array_map(
            fn (EloquentBrand $model) => $this->toDomain($model),
            $paginator->items()
        );

        return new PaginatedBrandsResult(
            items: $items,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage()
        );
    }

    /**
     * @return Brand[]
     */
    public function listAllActive(): array
    {
        $models = EloquentBrand::where('is_active', true)
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return $models->map(fn (EloquentBrand $m) => $this->toDomain($m))->all();
    }

    private function toDomain(EloquentBrand $model): Brand
    {
        return new Brand(
            id: new BrandId($model->id),
            name: new BrandName($model->name),
            slug: new BrandSlug($model->slug),
            description: BrandDescription::fromNullableString($model->description),
            logo: BrandLogo::fromNullableString($model->logo),
            isActive: new BrandStatus((bool) $model->is_active),
            position: (int) ($model->position ?? 0),
            createdAt: $model->created_at?->toISOString(),
            updatedAt: $model->updated_at?->toISOString()
        );
    }
}
