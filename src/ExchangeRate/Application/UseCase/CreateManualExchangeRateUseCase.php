<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Application\UseCase;

use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Domain\Contracts\UuidGenerator;

final class CreateManualExchangeRateUseCase
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $repository,
        private readonly UuidGenerator $generator,
        /**
         * Hallazgo Auditoria #4: zona en la que se decide la FECHA VALOR de la tasa.
         * Inyectada, no leida con `config()` aqui: los tests unitarios instancian este
         * caso de uso sin aplicacion levantada. El valor real lo pone el service provider.
         */
        private readonly string $businessTimezone = 'America/Caracas'
    ) {}

    public function execute(
        float|string $rateValue,
        ?string $rateDate = null,
        ?string $note = null,
        ?string $adminUserId = null
    ): ExchangeRate {
        $usd = CurrencyCode::usd();
        $ves = CurrencyCode::ves();

        // 1. Desactivar tasas anteriores
        $this->repository->deactivateAll($usd, $ves);

        // 2. Crear nueva entidad
        $exchangeRate = ExchangeRate::create(
            $this->generator,
            $usd,
            $ves,
            RateAmount::make($rateValue),
            RateSource::manual(),
            $rateDate ? RateDate::make($rateDate) : RateDate::today($this->businessTimezone),
            true,
            [
                'created_by_admin' => $adminUserId,
                'note' => $note ?? 'Tasa manual ingresada por el administrador.',
                'manual_entry_at' => date('c'),
            ]
        );

        // 3. Persistir
        $this->repository->save($exchangeRate);

        return $exchangeRate;
    }
}
