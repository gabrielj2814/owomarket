<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Application\UseCase;

use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;

final class ConvertCurrencyAmountUseCase
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $repository
    ) {}

    /**
     * Convierte un monto de USD a Bolívares (VES) usando la tasa activa.
     *
     * @return array{
     *     amount_usd: float,
     *     amount_ves: float,
     *     rate: float,
     *     rate_date: string,
     *     source: string
     * }
     */
    public function usdToVes(float $amountUsd): array
    {
        $usd = CurrencyCode::usd();
        $ves = CurrencyCode::ves();

        $activeRate = $this->repository->findActive($usd, $ves);

        $rateValue = $activeRate ? $activeRate->getRate()->value() : 1.0;
        $rateDate = $activeRate ? $activeRate->getRateDate()->value() : date('Y-m-d');
        $source = $activeRate ? $activeRate->getSource()->value() : 'FALLBACK';

        $amountVes = $activeRate ? $activeRate->convertUsdToVes($amountUsd) : round($amountUsd, 2);

        return [
            'amount_usd' => round($amountUsd, 2),
            'amount_ves' => $amountVes,
            'rate' => $rateValue,
            'rate_date' => $rateDate,
            'source' => $source,
        ];
    }

    /**
     * Convierte un monto de Bolívares (VES) a USD usando la tasa activa.
     *
     * @return array{
     *     amount_ves: float,
     *     amount_usd: float,
     *     rate: float,
     *     rate_date: string,
     *     source: string
     * }
     */
    public function vesToUsd(float $amountVes): array
    {
        $usd = CurrencyCode::usd();
        $ves = CurrencyCode::ves();

        $activeRate = $this->repository->findActive($usd, $ves);

        $rateValue = $activeRate ? $activeRate->getRate()->value() : 1.0;
        $rateDate = $activeRate ? $activeRate->getRateDate()->value() : date('Y-m-d');
        $source = $activeRate ? $activeRate->getSource()->value() : 'FALLBACK';

        $amountUsd = $activeRate ? $activeRate->convertVesToUsd($amountVes) : round($amountVes, 2);

        return [
            'amount_ves' => round($amountVes, 2),
            'amount_usd' => $amountUsd,
            'rate' => $rateValue,
            'rate_date' => $rateDate,
            'source' => $source,
        ];
    }
}
