<?php

declare(strict_types=1);

namespace Src\Tax\Domain\Entities;

use Src\Tax\Domain\ValueObjects\TaxRateId;
use Src\Tax\Domain\ValueObjects\TaxRateName;
use Src\Tax\Domain\ValueObjects\TaxRatePercentage;
use Src\Tax\Domain\ValueObjects\TaxRatePriority;
use Src\Tax\Domain\ValueObjects\TaxRateStatus;

final class TaxRate
{
    public function __construct(
        private ?TaxRateId $id,
        private TaxRateName $name,
        private TaxRatePercentage $rate,
        private ?string $country = null,
        private ?string $state = null,
        private ?string $city = null,
        private ?string $zip = null,
        private TaxRatePriority $priority = new TaxRatePriority(0),
        private TaxRateStatus $isActive = new TaxRateStatus(true),
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    public static function create(
        TaxRateName $name,
        TaxRatePercentage $rate,
        ?string $country = null,
        ?string $state = null,
        ?string $city = null,
        ?string $zip = null,
        ?TaxRatePriority $priority = null,
        ?TaxRateStatus $isActive = null
    ): self {
        return new self(
            id: null,
            name: $name,
            rate: $rate,
            country: $country ? trim($country) : null,
            state: $state ? trim($state) : null,
            city: $city ? trim($city) : null,
            zip: $zip ? trim($zip) : null,
            priority: $priority ?? TaxRatePriority::fromInt(0),
            isActive: $isActive ?? TaxRateStatus::active()
        );
    }

    public function id(): ?TaxRateId
    {
        return $this->id;
    }

    public function name(): TaxRateName
    {
        return $this->name;
    }

    public function rate(): TaxRatePercentage
    {
        return $this->rate;
    }

    public function country(): ?string
    {
        return $this->country;
    }

    public function state(): ?string
    {
        return $this->state;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function zip(): ?string
    {
        return $this->zip;
    }

    public function priority(): TaxRatePriority
    {
        return $this->priority;
    }

    public function isActive(): TaxRateStatus
    {
        return $this->isActive;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function activate(): void
    {
        $this->isActive = TaxRateStatus::active();
    }

    public function deactivate(): void
    {
        $this->isActive = TaxRateStatus::inactive();
    }

    public function changeName(TaxRateName $name): void
    {
        $this->name = $name;
    }

    public function changeRate(TaxRatePercentage $rate): void
    {
        $this->rate = $rate;
    }

    public function changeLocation(?string $country, ?string $state, ?string $city, ?string $zip): void
    {
        $this->country = $country ? trim($country) : null;
        $this->state = $state ? trim($state) : null;
        $this->city = $city ? trim($city) : null;
        $this->zip = $zip ? trim($zip) : null;
    }

    public function changePriority(TaxRatePriority $priority): void
    {
        $this->priority = $priority;
    }

    public function calculateTax(float $subtotal): float
    {
        if (! $this->isActive->value()) {
            return 0.0;
        }

        return round(($subtotal * $this->rate->value()) / 100, 2);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id?->value(),
            'name' => $this->name->value(),
            'rate' => $this->rate->value(),
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'zip' => $this->zip,
            'priority' => $this->priority->value(),
            'is_active' => $this->isActive->value(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
