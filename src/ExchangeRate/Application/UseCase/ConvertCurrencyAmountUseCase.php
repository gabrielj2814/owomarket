<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Application\UseCase;

use Exception;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
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
     *
     * @throws Exception si no hay tasa activa registrada.
     */
    public function usdToVes(float $amountUsd): array
    {
        $activeRate = $this->requireActiveRate();

        return [
            'amount_usd' => round($amountUsd, 2),
            'amount_ves' => $activeRate->convertUsdToVes($amountUsd),
            'rate' => $activeRate->getRate()->value(),
            'rate_date' => $activeRate->getRateDate()->value(),
            'source' => $activeRate->getSource()->value(),
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
     *
     * @throws Exception si no hay tasa activa registrada.
     */
    public function vesToUsd(float $amountVes): array
    {
        $activeRate = $this->requireActiveRate();

        return [
            'amount_ves' => round($amountVes, 2),
            'amount_usd' => $activeRate->convertVesToUsd($amountVes),
            'rate' => $activeRate->getRate()->value(),
            'rate_date' => $activeRate->getRateDate()->value(),
            'source' => $activeRate->getSource()->value(),
        ];
    }

    /**
     * Hallazgo D3: antes, si no había tasa activa se convertía con una tasa de 1.0
     * etiquetada como 'FALLBACK' y la respuesta seguía siendo `success: true`.
     * Escenario: `SyncBcvExchangeRateUseCase` desactivaba todas las tasas y fallaba al
     * guardar la nueva; a partir de ahí `/convert?amount=100` devolvía 100 Bs por 100 USD
     * (~775 veces menos) y el checkout en bolívares cobraba céntimos.
     * Ahora se falla en voz alta: es preferible un checkout caído a uno que cobra mal.
     *
     * @throws Exception
     */
    private function requireActiveRate(): ExchangeRate
    {
        $usd = CurrencyCode::usd();
        $ves = CurrencyCode::ves();

        $activeRate = $this->repository->findActive($usd, $ves);

        if (! $activeRate) {
            throw new Exception(
                "No existe una tasa de cambio activa para el par {$usd->value()} -> {$ves->value()}. ".
                'No es posible convertir montos hasta que se registre una.'
            );
        }

        return $activeRate;
    }
}
