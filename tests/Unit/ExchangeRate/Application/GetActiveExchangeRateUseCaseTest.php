<?php

declare(strict_types=1);

use Src\ExchangeRate\Application\UseCase\GetActiveExchangeRateUseCase;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

test('GetActiveExchangeRateUseCase returns active USD/VES rate', function () {
    $generator = new LaravelUuidGenerator;
    $rate = ExchangeRate::create(
        $generator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(775.3356),
        RateSource::bcv(),
        RateDate::today()
    );

    $mockRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $mockRepo->shouldReceive('findActive')->once()->andReturn($rate);

    $useCase = new GetActiveExchangeRateUseCase($mockRepo);
    $result = $useCase->execute();

    expect($result->getRate()->value())->toBe(775.3356);
    expect($result->isActive())->toBeTrue();
});
