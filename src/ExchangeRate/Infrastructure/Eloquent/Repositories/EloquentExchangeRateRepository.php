<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Eloquent\Repositories;

use DateTimeImmutable;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\ExchangeRate\Infrastructure\Eloquent\Models\ExchangeRate as EloquentExchangeRate;
use Src\Shared\Domain\ValueObjects\Uuid;

final class EloquentExchangeRateRepository implements ExchangeRateRepositoryInterface
{
    public function save(ExchangeRate $exchangeRate): void
    {
        EloquentExchangeRate::query()->updateOrCreate(
            ['id' => $exchangeRate->getId()->value()],
            [
                'base_currency' => $exchangeRate->getBaseCurrency()->value(),
                'target_currency' => $exchangeRate->getTargetCurrency()->value(),
                'rate' => $exchangeRate->getRate()->value(),
                'source' => $exchangeRate->getSource()->value(),
                'rate_date' => $exchangeRate->getRateDate()->value(),
                'is_active' => $exchangeRate->isActive(),
                'metadata' => $exchangeRate->getMetadata(),
            ]
        );
    }

    public function findActive(CurrencyCode $baseCurrency, CurrencyCode $targetCurrency): ?ExchangeRate
    {
        $model = EloquentExchangeRate::query()
            ->where('base_currency', $baseCurrency->value())
            ->where('target_currency', $targetCurrency->value())
            ->where('is_active', true)
            ->latest('rate_date')
            ->latest('created_at')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?ExchangeRate
    {
        $model = EloquentExchangeRate::query()->find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function deactivateAll(CurrencyCode $baseCurrency, CurrencyCode $targetCurrency): void
    {
        EloquentExchangeRate::query()
            ->where('base_currency', $baseCurrency->value())
            ->where('target_currency', $targetCurrency->value())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    public function listHistory(
        int $page = 1,
        int $perPage = 15,
        ?string $source = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $query = EloquentExchangeRate::query()
            ->orderBy('rate_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($source !== null && $source !== '') {
            $query->where('source', $source);
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $query->where('rate_date', '>=', $dateFrom);
        }

        if ($dateTo !== null && $dateTo !== '') {
            $query->where('rate_date', '<=', $dateTo);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = array_map(
            fn (EloquentExchangeRate $model) => $this->toDomain($model),
            $paginator->items()
        );

        return [
            'data' => $data,
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    private function toDomain(EloquentExchangeRate $model): ExchangeRate
    {
        $createdAt = $model->created_at ? DateTimeImmutable::createFromInterface($model->created_at) : null;
        $updatedAt = $model->updated_at ? DateTimeImmutable::createFromInterface($model->updated_at) : null;

        return ExchangeRate::reconstitute(
            Uuid::make($model->id),
            CurrencyCode::make($model->base_currency),
            CurrencyCode::make($model->target_currency),
            RateAmount::make($model->rate),
            RateSource::make($model->source),
            RateDate::make($model->rate_date?->format('Y-m-d') ?? date('Y-m-d')),
            (bool) $model->is_active,
            $model->metadata,
            $createdAt,
            $updatedAt
        );
    }
}
