<?php

declare(strict_types=1);

use Src\ExchangeRate\Application\UseCase\CreateManualExchangeRateUseCase;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

test('CreateManualExchangeRateUseCase deactivates old and creates manual rate', function () {
    $generator = new LaravelUuidGenerator;

    $mockRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $mockRepo->shouldReceive('deactivateAll')->once()->with(
        Mockery::on(fn (CurrencyCode $c) => $c->isUsd()),
        Mockery::on(fn (CurrencyCode $c) => $c->isVes())
    );
    $mockRepo->shouldReceive('save')->once()->with(
        Mockery::on(fn (ExchangeRate $r) => $r->getRate()->value() === 800.0 && $r->getSource()->isManual())
    );

    $useCase = new CreateManualExchangeRateUseCase($mockRepo, $generator);
    $result = $useCase->execute(800.0, '2026-08-19', 'Ajuste manual');

    expect($result->getRate()->value())->toBe(800.0);
    expect($result->getSource()->value())->toBe('MANUAL_ADMIN');
    expect($result->getMetadata()['note'])->toBe('Ajuste manual');
});
