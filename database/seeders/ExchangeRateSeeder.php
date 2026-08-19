<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Domain\Contracts\UuidGenerator;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ExchangeRateRepositoryInterface $repository, UuidGenerator $generator): void
    {
        $usd = CurrencyCode::usd();
        $ves = CurrencyCode::ves();

        $existing = $repository->findActive($usd, $ves);

        if (! $existing) {
            $exchangeRate = ExchangeRate::create(
                $generator,
                $usd,
                $ves,
                RateAmount::make(775.335600),
                RateSource::bcv(),
                RateDate::today(),
                true,
                [
                    'seeded' => true,
                    'note' => 'Tasa inicial oficial del BCV cargada por seeder.',
                ]
            );

            $repository->save($exchangeRate);
        }
    }
}
