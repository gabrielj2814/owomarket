<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Domain\Entities;

use DateTimeImmutable;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\Uuid;

final class ExchangeRate
{
    private function __construct(
        private readonly Uuid $id,
        private CurrencyCode $baseCurrency,
        private CurrencyCode $targetCurrency,
        private RateAmount $rate,
        private RateSource $source,
        private RateDate $rateDate,
        private bool $isActive = true,
        private ?array $metadata = null,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null
    ) {}

    public static function create(
        UuidGenerator $generator,
        CurrencyCode $baseCurrency,
        CurrencyCode $targetCurrency,
        RateAmount $rate,
        RateSource $source,
        RateDate $rateDate,
        bool $isActive = true,
        ?array $metadata = null
    ): self {
        $now = new DateTimeImmutable;

        return new self(
            Uuid::generate($generator),
            $baseCurrency,
            $targetCurrency,
            $rate,
            $source,
            $rateDate,
            $isActive,
            $metadata,
            $now,
            $now
        );
    }

    public static function reconstitute(
        Uuid $id,
        CurrencyCode $baseCurrency,
        CurrencyCode $targetCurrency,
        RateAmount $rate,
        RateSource $source,
        RateDate $rateDate,
        bool $isActive = true,
        ?array $metadata = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ): self {
        return new self(
            $id,
            $baseCurrency,
            $targetCurrency,
            $rate,
            $source,
            $rateDate,
            $isActive,
            $metadata,
            $createdAt,
            $updatedAt
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getBaseCurrency(): CurrencyCode
    {
        return $this->baseCurrency;
    }

    public function getTargetCurrency(): CurrencyCode
    {
        return $this->targetCurrency;
    }

    public function getRate(): RateAmount
    {
        return $this->rate;
    }

    public function getSource(): RateSource
    {
        return $this->source;
    }

    public function getRateDate(): RateDate
    {
        return $this->rateDate;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function convertUsdToVes(float $amountInUsd): float
    {
        return $this->rate->multiply($amountInUsd);
    }

    public function convertVesToUsd(float $amountInVes): float
    {
        return $this->rate->divide($amountInVes);
    }
}
