<?php

declare(strict_types=1);

namespace Src\Order\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\Order\Domain\Exceptions\EmptyOrderItemsException;
use Src\Order\Domain\Exceptions\InvalidOrderStateTransitionException;
use Src\Order\Domain\ValueObjects\Currency;
use Src\Order\Domain\ValueObjects\Money;
use Src\Order\Domain\ValueObjects\OrderId;
use Src\Order\Domain\ValueObjects\OrderNumber;
use Src\Order\Domain\ValueObjects\OrderStatus;
use Src\Order\Domain\ValueObjects\PaymentStatus;

final class Order
{
    private OrderId $id;

    private OrderNumber $orderNumber;

    private string $customerId;

    private OrderStatus $status;

    private Money $subtotal;

    private Money $taxAmount;

    private Money $shippingAmount;

    private Money $discountAmount;

    private Money $total;

    private Currency $currency;

    private string $paymentMethod;

    private PaymentStatus $paymentStatus;

    private ?string $shippingMethod;

    private ?string $notes;

    private ?string $customerNote;

    private ?DateTimeImmutable $confirmedAt;

    private ?DateTimeImmutable $cancelledAt;

    private ?DateTimeImmutable $shippedAt;

    private ?DateTimeImmutable $deliveredAt;

    private ?array $metadata;

    /** @var OrderItem[] */
    private array $items;

    private ?DateTimeImmutable $createdAt;

    private ?DateTimeImmutable $updatedAt;

    /**
     * @param  OrderItem[]  $items
     */
    public function __construct(
        OrderId $id,
        OrderNumber $orderNumber,
        string $customerId,
        OrderStatus $status,
        Money $subtotal,
        Money $taxAmount,
        Money $shippingAmount,
        Money $discountAmount,
        Money $total,
        Currency $currency,
        string $paymentMethod,
        PaymentStatus $paymentStatus,
        ?string $shippingMethod = null,
        ?string $notes = null,
        ?string $customerNote = null,
        ?DateTimeImmutable $confirmedAt = null,
        ?DateTimeImmutable $cancelledAt = null,
        ?DateTimeImmutable $shippedAt = null,
        ?DateTimeImmutable $deliveredAt = null,
        ?array $metadata = null,
        array $items = [],
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $trimmedCustomerId = trim($customerId);
        if (empty($trimmedCustomerId)) {
            throw new InvalidArgumentException('El ID del cliente no puede estar vacío.');
        }

        $trimmedPaymentMethod = trim($paymentMethod);
        if (empty($trimmedPaymentMethod)) {
            throw new InvalidArgumentException('El método de pago no puede estar vacío.');
        }

        $this->id = $id;
        $this->orderNumber = $orderNumber;
        $this->customerId = $trimmedCustomerId;
        $this->status = $status;
        $this->subtotal = $subtotal;
        $this->taxAmount = $taxAmount;
        $this->shippingAmount = $shippingAmount;
        $this->discountAmount = $discountAmount;
        $this->total = $total;
        $this->currency = $currency;
        $this->paymentMethod = $trimmedPaymentMethod;
        $this->paymentStatus = $paymentStatus;
        $this->shippingMethod = $shippingMethod;
        $this->notes = $notes;
        $this->customerNote = $customerNote;
        $this->confirmedAt = $confirmedAt;
        $this->cancelledAt = $cancelledAt;
        $this->shippedAt = $shippedAt;
        $this->deliveredAt = $deliveredAt;
        $this->metadata = $metadata;
        $this->items = $items;
        $this->createdAt = $createdAt ?? new DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable;
    }

    /**
     * Factory constructor for creating a new Order.
     *
     * @param  OrderItem[]  $items
     */
    public static function create(
        string $customerId,
        string $paymentMethod,
        array $items,
        float|int $taxAmount = 0.0,
        float|int $shippingAmount = 0.0,
        float|int $discountAmount = 0.0,
        ?string $orderNumber = null,
        ?string $currency = 'USD',
        ?string $shippingMethod = null,
        ?string $notes = null,
        ?string $customerNote = null,
        ?array $metadata = null,
        ?string $id = null
    ): self {
        if (empty($items)) {
            throw new EmptyOrderItemsException;
        }

        $orderId = new OrderId($id);
        $orderNum = $orderNumber !== null ? new OrderNumber($orderNumber) : OrderNumber::generate();

        // Calculate subtotal from items
        $calculatedSubtotal = 0.0;
        foreach ($items as $item) {
            $item->setOrderId($orderId);
            $calculatedSubtotal += $item->total()->amount();
        }

        $subtotalMoney = Money::from($calculatedSubtotal);
        $taxMoney = Money::from($taxAmount);
        $shippingMoney = Money::from($shippingAmount);
        $discountMoney = Money::from($discountAmount);

        $calculatedTotal = max(0.0, ($subtotalMoney->amount() + $taxMoney->amount() + $shippingMoney->amount()) - $discountMoney->amount());
        $totalMoney = Money::from($calculatedTotal);

        return new self(
            $orderId,
            $orderNum,
            $customerId,
            OrderStatus::PENDING,
            $subtotalMoney,
            $taxMoney,
            $shippingMoney,
            $discountMoney,
            $totalMoney,
            new Currency($currency),
            $paymentMethod,
            PaymentStatus::PENDING,
            $shippingMethod,
            $notes,
            $customerNote,
            null,
            null,
            null,
            null,
            $metadata,
            $items,
            new DateTimeImmutable,
            new DateTimeImmutable
        );
    }

    // --- State Machine & Business Logic ---

    public function confirm(): void
    {
        if (! $this->status->canBeConfirmed()) {
            throw InvalidOrderStateTransitionException::from($this->status->value, OrderStatus::CONFIRMED->value);
        }

        $this->status = OrderStatus::CONFIRMED;
        $this->confirmedAt = new DateTimeImmutable;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function process(): void
    {
        if (! $this->status->canBeProcessed()) {
            throw InvalidOrderStateTransitionException::from($this->status->value, OrderStatus::PROCESSING->value);
        }

        $this->status = OrderStatus::PROCESSING;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function markAsShipped(?string $shippingMethod = null): void
    {
        if (! $this->status->canBeShipped()) {
            throw InvalidOrderStateTransitionException::from($this->status->value, OrderStatus::SHIPPED->value);
        }

        $this->status = OrderStatus::SHIPPED;
        $this->shippedAt = new DateTimeImmutable;
        if ($shippingMethod !== null) {
            $this->shippingMethod = $shippingMethod;
        }
        $this->updatedAt = new DateTimeImmutable;
    }

    public function markAsDelivered(): void
    {
        if (! $this->status->canBeDelivered()) {
            throw InvalidOrderStateTransitionException::from($this->status->value, OrderStatus::DELIVERED->value);
        }

        $this->status = OrderStatus::DELIVERED;
        $this->deliveredAt = new DateTimeImmutable;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function cancel(?string $reason = null): void
    {
        if (! $this->status->canBeCancelled()) {
            throw InvalidOrderStateTransitionException::from($this->status->value, OrderStatus::CANCELLED->value);
        }

        $this->status = OrderStatus::CANCELLED;
        $this->cancelledAt = new DateTimeImmutable;
        if (! empty($reason)) {
            $this->notes = $this->notes ? "{$this->notes} | Cancelación: {$reason}" : "Cancelación: {$reason}";
        }
        $this->updatedAt = new DateTimeImmutable;
    }

    public function refund(): void
    {
        if (! $this->status->canBeRefunded()) {
            throw InvalidOrderStateTransitionException::from($this->status->value, OrderStatus::REFUNDED->value);
        }

        // Hallazgo OR1: esta linea escribia el estado del pago sin guarda alguna, asi que
        // un pedido `confirmed` cuyo pago nunca paso de `pending` quedaba como reembolsado:
        // se "devolvia" dinero que jamas entro.
        if (! $this->paymentStatus->canBeRefunded()) {
            throw InvalidOrderStateTransitionException::payment($this->paymentStatus->value, PaymentStatus::REFUNDED->value);
        }

        $this->status = OrderStatus::REFUNDED;
        $this->paymentStatus = PaymentStatus::REFUNDED;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function markPaymentPaid(): void
    {
        if (! $this->paymentStatus->canBePaid()) {
            throw InvalidOrderStateTransitionException::payment($this->paymentStatus->value, PaymentStatus::PAID->value);
        }

        $this->paymentStatus = PaymentStatus::PAID;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function markPaymentFailed(): void
    {
        if (! $this->paymentStatus->canBeFailed()) {
            throw InvalidOrderStateTransitionException::payment($this->paymentStatus->value, PaymentStatus::FAILED->value);
        }

        $this->paymentStatus = PaymentStatus::FAILED;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function updateNotes(?string $notes, ?string $customerNote): void
    {
        $this->notes = $notes;
        $this->customerNote = $customerNote;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function addItem(OrderItem $item): void
    {
        $item->setOrderId($this->id);
        $this->items[] = $item;
        $this->recalculateTotals();
    }

    public function recalculateTotals(): void
    {
        $calculatedSubtotal = 0.0;
        foreach ($this->items as $item) {
            $calculatedSubtotal += $item->total()->amount();
        }

        $this->subtotal = Money::from($calculatedSubtotal);
        $calculatedTotal = max(0.0, ($this->subtotal->amount() + $this->taxAmount->amount() + $this->shippingAmount->amount()) - $this->discountAmount->amount());
        $this->total = Money::from($calculatedTotal);
        $this->updatedAt = new DateTimeImmutable;
    }

    // --- Getters ---

    public function id(): OrderId
    {
        return $this->id;
    }

    public function orderNumber(): OrderNumber
    {
        return $this->orderNumber;
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function subtotal(): Money
    {
        return $this->subtotal;
    }

    public function taxAmount(): Money
    {
        return $this->taxAmount;
    }

    public function shippingAmount(): Money
    {
        return $this->shippingAmount;
    }

    public function discountAmount(): Money
    {
        return $this->discountAmount;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function paymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function shippingMethod(): ?string
    {
        return $this->shippingMethod;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function customerNote(): ?string
    {
        return $this->customerNote;
    }

    public function confirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function cancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function shippedAt(): ?DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function deliveredAt(): ?DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function metadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @return OrderItem[]
     */
    public function items(): array
    {
        return $this->items;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'order_number' => $this->orderNumber->value(),
            'customer_id' => $this->customerId,
            'status' => $this->status->value,
            'subtotal' => $this->subtotal->amount(),
            'tax_amount' => $this->taxAmount->amount(),
            'shipping_amount' => $this->shippingAmount->amount(),
            'discount_amount' => $this->discountAmount->amount(),
            'total' => $this->total->amount(),
            'currency' => $this->currency->code(),
            'payment_method' => $this->paymentMethod,
            'payment_status' => $this->paymentStatus->value,
            'shipping_method' => $this->shippingMethod,
            'notes' => $this->notes,
            'customer_note' => $this->customerNote,
            'confirmed_at' => $this->confirmedAt?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelledAt?->format('Y-m-d H:i:s'),
            'shipped_at' => $this->shippedAt?->format('Y-m-d H:i:s'),
            'delivered_at' => $this->deliveredAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
            'items' => array_map(fn (OrderItem $item) => $item->toArray(), $this->items),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
