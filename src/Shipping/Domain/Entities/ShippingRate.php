<?php

declare(strict_types=1);

namespace Src\Shipping\Domain\Entities;

use Src\Shipping\Domain\ValueObjects\ShippingRateCost;
use Src\Shipping\Domain\ValueObjects\ShippingRateId;
use Src\Shipping\Domain\ValueObjects\ShippingRateName;
use Src\Shipping\Domain\ValueObjects\ShippingRateType;
use Src\Shipping\Domain\ValueObjects\ShippingStatus;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;

final class ShippingRate
{
    public function __construct(
        private ?ShippingRateId $id,
        private ShippingZoneId $shippingZoneId,
        private ShippingRateName $name,
        private ShippingRateType $type,
        private ShippingRateCost $cost,
        private ?float $minValue = null,
        private ?float $maxValue = null,
        private ShippingStatus $isActive = new ShippingStatus(true),
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    public static function create(
        ShippingZoneId $shippingZoneId,
        ShippingRateName $name,
        ShippingRateType $type,
        ShippingRateCost $cost,
        ?float $minValue = null,
        ?float $maxValue = null,
        ?ShippingStatus $isActive = null
    ): self {
        return new self(
            id: null,
            shippingZoneId: $shippingZoneId,
            name: $name,
            type: $type,
            cost: $cost,
            minValue: $minValue,
            maxValue: $maxValue,
            isActive: $isActive ?? ShippingStatus::active()
        );
    }

    public function id(): ?ShippingRateId
    {
        return $this->id;
    }

    public function shippingZoneId(): ShippingZoneId
    {
        return $this->shippingZoneId;
    }

    public function name(): ShippingRateName
    {
        return $this->name;
    }

    public function type(): ShippingRateType
    {
        return $this->type;
    }

    public function cost(): ShippingRateCost
    {
        return $this->cost;
    }

    public function minValue(): ?float
    {
        return $this->minValue;
    }

    public function maxValue(): ?float
    {
        return $this->maxValue;
    }

    public function isActive(): ShippingStatus
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
        $this->isActive = ShippingStatus::active();
    }

    public function deactivate(): void
    {
        $this->isActive = ShippingStatus::inactive();
    }

    public function changeDetails(
        ShippingRateName $name,
        ShippingRateType $type,
        ShippingRateCost $cost,
        ?float $minValue,
        ?float $maxValue
    ): void {
        $this->name = $name;
        $this->type = $type;
        $this->cost = $cost;
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
    }

    public function appliesTo(float $orderValue, float $totalWeight = 0.0): bool
    {
        if (! $this->isActive->value()) {
            return false;
        }

        // Hallazgo D5: aqui habia un `if ($this->type->isFree()) return true;` ANTES de
        // evaluar `minValue`/`maxValue`, asi que un «Envio gratis a partir de $100» se
        // aplicaba a un pedido de $5. Y como era la opcion mas barata,
        // `CalculateShippingOptionsUseCase` la marcaba como recomendada: **todos los
        // envios salian gratis**.
        //
        // Una tarifa gratuita tiene exactamente los mismos umbrales que cualquier otra;
        // lo unico que la distingue es que su coste es 0.
        $comparisonValue = $this->type->isWeightBased() ? $totalWeight : $orderValue;

        if ($this->minValue !== null && $comparisonValue < $this->minValue) {
            return false;
        }

        if ($this->maxValue !== null && $comparisonValue > $this->maxValue) {
            return false;
        }

        return true;
    }

    /**
     * Hallazgo D5: no recibia ni el peso ni el valor del pedido, asi que una tarifa
     * «$3 por kg» cobraba **$3 por un pedido de 20 kg** en vez de $60.
     *
     * Para `weight_based` el coste configurado es el precio por unidad de peso; para el
     * resto es el importe plano. `free` sigue siendo gratis, pero ahora solo se ofrece
     * cuando el pedido cumple sus umbrales.
     */
    public function calculateCost(float $orderValue = 0.0, float $totalWeight = 0.0): float
    {
        if ($this->type->isFree()) {
            return 0.0;
        }

        if ($this->type->isWeightBased()) {
            return round($this->cost->value() * max(0.0, $totalWeight), 2);
        }

        return $this->cost->value();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id?->value(),
            'shipping_zone_id' => $this->shippingZoneId->value(),
            'name' => $this->name->value(),
            'type' => $this->type->value(),
            'cost' => $this->cost->value(),
            'min_value' => $this->minValue,
            'max_value' => $this->maxValue,
            'is_active' => $this->isActive->value(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
