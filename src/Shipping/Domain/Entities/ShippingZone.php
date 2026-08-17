<?php

declare(strict_types=1);

namespace Src\Shipping\Domain\Entities;

use Src\Shipping\Domain\ValueObjects\ShippingStatus;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;
use Src\Shipping\Domain\ValueObjects\ShippingZoneName;

final class ShippingZone
{
    /**
     * @param  ShippingRate[]  $rates
     */
    public function __construct(
        private ?ShippingZoneId $id,
        private ShippingZoneName $name,
        private ?array $countries = null,
        private ?array $states = null,
        private ?array $postalCodes = null,
        private int $priority = 0,
        private ShippingStatus $isActive = new ShippingStatus(true),
        private array $rates = [],
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    public static function create(
        ShippingZoneName $name,
        ?array $countries = null,
        ?array $states = null,
        ?array $postalCodes = null,
        int $priority = 0,
        ?ShippingStatus $isActive = null,
        array $rates = []
    ): self {
        return new self(
            id: null,
            name: $name,
            countries: $countries,
            states: $states,
            postalCodes: $postalCodes,
            priority: $priority,
            isActive: $isActive ?? ShippingStatus::active(),
            rates: $rates
        );
    }

    public function id(): ?ShippingZoneId
    {
        return $this->id;
    }

    public function name(): ShippingZoneName
    {
        return $this->name;
    }

    public function countries(): ?array
    {
        return $this->countries;
    }

    public function states(): ?array
    {
        return $this->states;
    }

    public function postalCodes(): ?array
    {
        return $this->postalCodes;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function isActive(): ShippingStatus
    {
        return $this->isActive;
    }

    /**
     * @return ShippingRate[]
     */
    public function rates(): array
    {
        return $this->rates;
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
        $this->isActive = ShippingStatus::active();
    }

    public function deactivate(): void
    {
        $this->isActive = ShippingStatus::inactive();
    }

    public function changeName(ShippingZoneName $name): void
    {
        $this->name = $name;
    }

    public function changeLocations(?array $countries, ?array $states, ?array $postalCodes): void
    {
        $this->countries = $countries;
        $this->states = $states;
        $this->postalCodes = $postalCodes;
    }

    public function changePriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @param  ShippingRate[]  $rates
     */
    public function setRates(array $rates): void
    {
        $this->rates = $rates;
    }

    public function matchesLocation(?string $country = null, ?string $state = null, ?string $postalCode = null): bool
    {
        if (! $this->isActive->value()) {
            return false;
        }

        // Si no tiene restricciones, aplica a todos
        if (empty($this->countries) && empty($this->states) && empty($this->postalCodes)) {
            return true;
        }

        if ($country !== null && ! empty($this->countries) && ! in_array($country, $this->countries, true)) {
            return false;
        }

        if ($state !== null && ! empty($this->states) && ! in_array($state, $this->states, true)) {
            return false;
        }

        if ($postalCode !== null && ! empty($this->postalCodes) && ! in_array($postalCode, $this->postalCodes, true)) {
            return false;
        }

        return true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id?->value(),
            'name' => $this->name->value(),
            'countries' => $this->countries,
            'states' => $this->states,
            'postal_codes' => $this->postalCodes,
            'priority' => $this->priority,
            'is_active' => $this->isActive->value(),
            'rates' => array_map(fn (ShippingRate $r) => $r->toArray(), $this->rates),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
