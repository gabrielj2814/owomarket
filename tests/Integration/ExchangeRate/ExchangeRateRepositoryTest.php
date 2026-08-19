<?php

declare(strict_types=1);

use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\ExchangeRate\Infrastructure\Eloquent\Repositories\EloquentExchangeRateRepository;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

beforeEach(function () {
    $this->repository = new EloquentExchangeRateRepository;
    $this->generator = new LaravelUuidGenerator;
});

test('it saves and retrieves active exchange rate from central database', function () {
    $usd = CurrencyCode::usd();
    $ves = CurrencyCode::ves();

    $rate = ExchangeRate::create(
        $this->generator,
        $usd,
        $ves,
        RateAmount::make(775.335600),
        RateSource::bcv(),
        RateDate::today()
    );

    $this->repository->save($rate);

    $found = $this->repository->findActive($usd, $ves);

    expect($found)->not->toBeNull();
    expect($found->getId()->equals($rate->getId()))->toBeTrue();
    expect($found->getRate()->value())->toBe(775.3356);
    expect($found->getSource()->value())->toBe('BCV_SCRAPING');
});

test('it deactivates previous rates when new rate is registered', function () {
    $usd = CurrencyCode::usd();
    $ves = CurrencyCode::ves();

    $rate1 = ExchangeRate::create(
        $this->generator,
        $usd,
        $ves,
        RateAmount::make(700.0),
        RateSource::bcv(),
        RateDate::make('2026-08-01')
    );
    $this->repository->save($rate1);

    // Deactivate previous
    $this->repository->deactivateAll($usd, $ves);

    $rate2 = ExchangeRate::create(
        $this->generator,
        $usd,
        $ves,
        RateAmount::make(775.335600),
        RateSource::bcv(),
        RateDate::today()
    );
    $this->repository->save($rate2);

    $active = $this->repository->findActive($usd, $ves);
    expect($active)->not->toBeNull();
    expect($active->getRate()->value())->toBe(775.3356);

    $old = $this->repository->findById($rate1->getId());
    expect($old->isActive())->toBeFalse();
});

test('it lists exchange rate history with pagination', function () {
    $usd = CurrencyCode::usd();
    $ves = CurrencyCode::ves();

    for ($i = 1; $i <= 5; $i++) {
        $rate = ExchangeRate::create(
            $this->generator,
            $usd,
            $ves,
            RateAmount::make(700.0 + $i),
            RateSource::bcv(),
            RateDate::make("2026-08-0{$i}"),
            $i === 5
        );
        $this->repository->save($rate);
    }

    $history = $this->repository->listHistory(page: 1, perPage: 3);

    expect($history['total'])->toBe(5);
    expect(count($history['data']))->toBe(3);
    expect($history['last_page'])->toBe(2);
});
