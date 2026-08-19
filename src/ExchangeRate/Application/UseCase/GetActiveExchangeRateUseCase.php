<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Application\UseCase;

use Exception;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;

final class GetActiveExchangeRateUseCase
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $repository
    ) {}

    public function execute(?CurrencyCode $baseCurrency = null, ?CurrencyCode $targetCurrency = null): ExchangeRate
    {
        $base = $baseCurrency ?? CurrencyCode::usd();
        $target = $targetCurrency ?? CurrencyCode::ves();

        $rate = $this->repository->findActive($base, $target);

        if (! $rate) {
            throw new Exception("No existe una tasa de cambio activa para el par {$base->value()} -> {$target->value()}.");
        }

        return $rate;
    }
}
