<?php

declare(strict_types=1);

namespace Src\Order\Domain\Entities;

use InvalidArgumentException;
use Src\Order\Domain\ValueObjects\Money;
use Src\Order\Domain\ValueObjects\OrderId;
use Src\Order\Domain\ValueObjects\OrderItemId;

final class OrderItem
{
    private OrderItemId $id;

    private ?OrderId $orderId;

    private string $productId;

    private ?string $productVariantId;

    private string $productName;

    private string $sku;

    private Money $price;

    private int $quantity;

    private ?array $attributes;

    private Money $total;

    public function __construct(
        OrderItemId $id,
        ?OrderId $orderId,
        string $productId,
        ?string $productVariantId,
        string $productName,
        string $sku,
        Money $price,
        int $quantity,
        ?array $attributes = null,
        ?Money $total = null
    ) {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("La cantidad del ítem debe ser mayor o igual a 1. Se recibió: {$quantity}.");
        }

        $trimmedProductName = trim($productName);
        if (empty($trimmedProductName)) {
            throw new InvalidArgumentException('El nombre del producto del ítem de la orden no puede estar vacío.');
        }

        $this->id = $id;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->productVariantId = $productVariantId;
        $this->productName = $trimmedProductName;
        $this->sku = trim($sku);
        $this->price = $price;
        $this->quantity = $quantity;
        $this->attributes = $attributes;
        $this->total = $total ?? $price->multiply($quantity);
    }

    public static function create(
        string $productId,
        string $productName,
        string $sku,
        float $price,
        int $quantity,
        ?string $productVariantId = null,
        ?array $attributes = null,
        ?string $id = null,
        ?string $orderId = null
    ): self {
        return new self(
            new OrderItemId($id),
            $orderId !== null ? new OrderId($orderId) : null,
            $productId,
            $productVariantId,
            $productName,
            $sku,
            Money::from($price),
            $quantity,
            $attributes
        );
    }

    public function id(): OrderItemId
    {
        return $this->id;
    }

    public function orderId(): ?OrderId
    {
        return $this->orderId;
    }

    public function setOrderId(OrderId $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function productVariantId(): ?string
    {
        return $this->productVariantId;
    }

    public function productName(): string
    {
        return $this->productName;
    }

    public function sku(): string
    {
        return $this->sku;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function attributes(): ?array
    {
        return $this->attributes;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'order_id' => $this->orderId?->value(),
            'product_id' => $this->productId,
            'product_variant_id' => $this->productVariantId,
            'product_name' => $this->productName,
            'sku' => $this->sku,
            'price' => $this->price->amount(),
            'quantity' => $this->quantity,
            'attributes' => $this->attributes,
            'total' => $this->total->amount(),
        ];
    }
}
