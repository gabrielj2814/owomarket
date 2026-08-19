<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Eloquent\Repositories;

use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Application\DTOs\PaginatedShippingZonesResult;
use Src\Shipping\Application\DTOs\ShippingZoneFilterCriteria;
use Src\Shipping\Domain\Entities\ShippingRate;
use Src\Shipping\Domain\Entities\ShippingZone;
use Src\Shipping\Domain\ValueObjects\ShippingRateCost;
use Src\Shipping\Domain\ValueObjects\ShippingRateId;
use Src\Shipping\Domain\ValueObjects\ShippingRateName;
use Src\Shipping\Domain\ValueObjects\ShippingRateType;
use Src\Shipping\Domain\ValueObjects\ShippingStatus;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;
use Src\Shipping\Domain\ValueObjects\ShippingZoneName;
use Src\Shipping\Infrastructure\Eloquent\Models\ShippingRate as EloquentShippingRate;
use Src\Shipping\Infrastructure\Eloquent\Models\ShippingZone as EloquentShippingZone;

final class ShippingRepository implements ShippingRepositoryInterface
{
    public function saveZone(ShippingZone $zone): ShippingZone
    {
        $model = EloquentShippingZone::create([
            'name' => $zone->name()->value(),
            'countries' => $zone->countries(),
            'states' => $zone->states(),
            'postal_codes' => $zone->postalCodes(),
            'priority' => $zone->priority(),
            'is_active' => $zone->isActive()->value(),
        ]);

        return $this->toZoneDomain($model);
    }

    public function findZoneById(ShippingZoneId $id): ?ShippingZone
    {
        $model = EloquentShippingZone::with('rates')->find($id->value());

        return $model ? $this->toZoneDomain($model) : null;
    }

    public function updateZone(ShippingZone $zone): ShippingZone
    {
        $model = EloquentShippingZone::findOrFail($zone->id()->value());

        $model->update([
            'name' => $zone->name()->value(),
            'countries' => $zone->countries(),
            'states' => $zone->states(),
            'postal_codes' => $zone->postalCodes(),
            'priority' => $zone->priority(),
            'is_active' => $zone->isActive()->value(),
        ]);

        return $this->toZoneDomain($model->fresh('rates'));
    }

    public function deleteZone(ShippingZoneId $id): void
    {
        EloquentShippingRate::where('shipping_zone_id', $id->value())->delete();
        EloquentShippingZone::where('id', $id->value())->delete();
    }

    public function filterZones(ShippingZoneFilterCriteria $criteria): PaginatedShippingZonesResult
    {
        $query = EloquentShippingZone::with('rates');

        if ($criteria->search !== null && trim($criteria->search) !== '') {
            $search = '%'.trim($criteria->search).'%';
            $query->where('name', 'like', $search);
        }

        if ($criteria->isActive !== null) {
            $query->where('is_active', $criteria->isActive);
        }

        $allowedSorts = ['id', 'name', 'priority', 'is_active', 'created_at'];
        $sortBy = in_array($criteria->sortBy, $allowedSorts, true) ? $criteria->sortBy : 'priority';
        $sortDirection = strtolower($criteria->sortDirection) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page
        );

        $items = array_map(
            fn (EloquentShippingZone $model) => $this->toZoneDomain($model),
            $paginator->items()
        );

        return new PaginatedShippingZonesResult(
            items: $items,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage()
        );
    }

    /**
     * @return ShippingZone[]
     */
    public function findMatchingZones(?string $country = null, ?string $state = null, ?string $postalCode = null): array
    {
        $models = EloquentShippingZone::with(['rates' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        $matching = [];
        foreach ($models as $m) {
            $zoneDomain = $this->toZoneDomain($m);
            if ($zoneDomain->matchesLocation($country, $state, $postalCode)) {
                $matching[] = $zoneDomain;
            }
        }

        return $matching;
    }

    public function saveRate(ShippingRate $rate): ShippingRate
    {
        $model = EloquentShippingRate::create([
            'shipping_zone_id' => $rate->shippingZoneId()->value(),
            'name' => $rate->name()->value(),
            'type' => $rate->type()->value(),
            'cost' => $rate->cost()->value(),
            'min_value' => $rate->minValue(),
            'max_value' => $rate->maxValue(),
            'is_active' => $rate->isActive()->value(),
        ]);

        return $this->toRateDomain($model);
    }

    public function findRateById(ShippingRateId $id): ?ShippingRate
    {
        $model = EloquentShippingRate::find($id->value());

        return $model ? $this->toRateDomain($model) : null;
    }

    public function deleteRate(ShippingRateId $id): void
    {
        EloquentShippingRate::where('id', $id->value())->delete();
    }

    private function toZoneDomain(EloquentShippingZone $model): ShippingZone
    {
        $rates = [];
        if ($model->relationLoaded('rates') && $model->rates) {
            $rates = $model->rates->map(fn (EloquentShippingRate $r) => $this->toRateDomain($r))->all();
        }

        return new ShippingZone(
            id: ShippingZoneId::fromString((string) $model->id),
            name: ShippingZoneName::make($model->name),
            countries: $model->countries,
            states: $model->states,
            postalCodes: $model->postal_codes,
            priority: (int) ($model->priority ?? 0),
            isActive: ShippingStatus::fromBool((bool) $model->is_active),
            rates: $rates,
            createdAt: $model->created_at?->toISOString(),
            updatedAt: $model->updated_at?->toISOString()
        );
    }

    private function toRateDomain(EloquentShippingRate $model): ShippingRate
    {
        return new ShippingRate(
            id: ShippingRateId::fromString((string) $model->id),
            shippingZoneId: ShippingZoneId::fromString((string) $model->shipping_zone_id),
            name: ShippingRateName::make($model->name),
            type: ShippingRateType::fromString($model->type),
            cost: ShippingRateCost::fromFloat((float) $model->cost),
            minValue: $model->min_value !== null ? (float) $model->min_value : null,
            maxValue: $model->max_value !== null ? (float) $model->max_value : null,
            isActive: ShippingStatus::fromBool((bool) $model->is_active),
            createdAt: $model->created_at?->toISOString(),
            updatedAt: $model->updated_at?->toISOString()
        );
    }
}
