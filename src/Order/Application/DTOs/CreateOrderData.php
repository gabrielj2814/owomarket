<?php

declare(strict_types=1);

namespace Src\Order\Application\DTOs;

final class CreateOrderData
{
    /**
     * @param  OrderItemInputData[]  $items
     */
    public function __construct(
        public readonly string $customerId,
        public readonly string $paymentMethod,
        public readonly array $items,
        public readonly float $taxAmount = 0.0,
        public readonly float $shippingAmount = 0.0,
        public readonly float $discountAmount = 0.0,
        public readonly ?string $orderNumber = null,
        public readonly ?string $currency = 'USD',
        public readonly ?string $shippingMethod = null,
        public readonly ?string $notes = null,
        public readonly ?string $customerNote = null,
        public readonly ?array $metadata = null,
        public readonly ?string $id = null
    ) {}

    public static function fromArray(array $data): self
    {
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                if ($itemData instanceof OrderItemInputData) {
                    $items[] = $itemData;
                } elseif (is_array($itemData)) {
                    $items[] = OrderItemInputData::fromArray($itemData);
                }
            }
        }

        return new self(
            customerId: (string) ($data['customer_id'] ?? ''),
            paymentMethod: (string) ($data['payment_method'] ?? 'manual'),
            items: $items,
            taxAmount: (float) ($data['tax_amount'] ?? 0.0),
            shippingAmount: (float) ($data['shipping_amount'] ?? 0.0),
            discountAmount: (float) ($data['discount_amount'] ?? 0.0),
            orderNumber: isset($data['order_number']) && ! empty($data['order_number']) ? (string) $data['order_number'] : null,
            currency: (string) ($data['currency'] ?? 'USD'),
            shippingMethod: isset($data['shipping_method']) && ! empty($data['shipping_method']) ? (string) $data['shipping_method'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            customerNote: isset($data['customer_note']) ? (string) $data['customer_note'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            id: isset($data['id']) && ! empty($data['id']) ? (string) $data['id'] : null
        );
    }
}
