<?php

declare(strict_types=1);

namespace Src\Tax\Infrastructure\Eloquent\Repositories;

use Src\Tax\Application\Contracts\TaxRateRepositoryInterface;
use Src\Tax\Application\DTOs\PaginatedTaxRatesResult;
use Src\Tax\Application\DTOs\TaxRateFilterCriteria;
use Src\Tax\Domain\Entities\TaxRate;
use Src\Tax\Domain\ValueObjects\TaxRateId;
use Src\Tax\Domain\ValueObjects\TaxRateName;
use Src\Tax\Domain\ValueObjects\TaxRatePercentage;
use Src\Tax\Domain\ValueObjects\TaxRatePriority;
use Src\Tax\Domain\ValueObjects\TaxRateStatus;
use Src\Tax\Infrastructure\Eloquent\Models\TaxRate as EloquentTaxRate;

final class TaxRateRepository implements TaxRateRepositoryInterface
{
    public function save(TaxRate $taxRate): TaxRate
    {
        $model = EloquentTaxRate::create([
            'name' => $taxRate->name()->value(),
            'rate' => $taxRate->rate()->value(),
            'country' => $taxRate->country(),
            'state' => $taxRate->state(),
            'city' => $taxRate->city(),
            'zip' => $taxRate->zip(),
            'priority' => $taxRate->priority()->value(),
            'is_active' => $taxRate->isActive()->value(),
        ]);

        return $this->toDomain($model);
    }

    public function findById(TaxRateId $id): ?TaxRate
    {
        $model = EloquentTaxRate::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function update(TaxRate $taxRate): TaxRate
    {
        $model = EloquentTaxRate::findOrFail($taxRate->id()->value());

        $model->update([
            'name' => $taxRate->name()->value(),
            'rate' => $taxRate->rate()->value(),
            'country' => $taxRate->country(),
            'state' => $taxRate->state(),
            'city' => $taxRate->city(),
            'zip' => $taxRate->zip(),
            'priority' => $taxRate->priority()->value(),
            'is_active' => $taxRate->isActive()->value(),
        ]);

        return $this->toDomain($model->fresh());
    }

    public function delete(TaxRateId $id): void
    {
        EloquentTaxRate::where('id', $id->value())->delete();
    }

    public function filter(TaxRateFilterCriteria $criteria): PaginatedTaxRatesResult
    {
        $query = EloquentTaxRate::query();

        if ($criteria->search !== null && trim($criteria->search) !== '') {
            $search = '%'.trim($criteria->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('country', 'like', $search)
                    ->orWhere('state', 'like', $search)
                    ->orWhere('city', 'like', $search);
            });
        }

        if ($criteria->country !== null && trim($criteria->country) !== '') {
            $query->where('country', $criteria->country);
        }

        if ($criteria->state !== null && trim($criteria->state) !== '') {
            $query->where('state', $criteria->state);
        }

        if ($criteria->isActive !== null) {
            $query->where('is_active', $criteria->isActive);
        }

        $allowedSorts = ['id', 'name', 'rate', 'priority', 'is_active', 'created_at'];
        $sortBy = in_array($criteria->sortBy, $allowedSorts, true) ? $criteria->sortBy : 'priority';
        $sortDirection = strtolower($criteria->sortDirection) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page
        );

        $items = array_map(
            fn (EloquentTaxRate $model) => $this->toDomain($model),
            $paginator->items()
        );

        return new PaginatedTaxRatesResult(
            items: $items,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage()
        );
    }

    /**
     * @return TaxRate[]
     */
    public function findApplicableRates(?string $country = null, ?string $state = null, ?string $city = null, ?string $zip = null): array
    {
        $query = EloquentTaxRate::where('is_active', true);

        if ($country !== null) {
            $query->where(function ($q) use ($country) {
                $q->whereNull('country')->orWhere('country', '')->orWhere('country', $country);
            });
        }

        if ($state !== null) {
            $query->where(function ($q) use ($state) {
                $q->whereNull('state')->orWhere('state', '')->orWhere('state', $state);
            });
        }

        if ($city !== null) {
            $query->where(function ($q) use ($city) {
                $q->whereNull('city')->orWhere('city', '')->orWhere('city', $city);
            });
        }

        if ($zip !== null) {
            $query->where(function ($q) use ($zip) {
                $q->whereNull('zip')->orWhere('zip', '')->orWhere('zip', $zip);
            });
        }

        $models = $query->orderBy('priority', 'asc')->get();

        return $models->map(fn (EloquentTaxRate $m) => $this->toDomain($m))->all();
    }

    private function toDomain(EloquentTaxRate $model): TaxRate
    {
        return new TaxRate(
            id: TaxRateId::fromString((string) $model->id),
            name: TaxRateName::make($model->name),
            rate: TaxRatePercentage::create((float) $model->rate),
            country: $model->country,
            state: $model->state,
            city: $model->city,
            zip: $model->zip,
            priority: TaxRatePriority::fromInt((int) ($model->priority ?? 0)),
            isActive: TaxRateStatus::fromBool((bool) $model->is_active),
            createdAt: $model->created_at?->toISOString(),
            updatedAt: $model->updated_at?->toISOString()
        );
    }
}
