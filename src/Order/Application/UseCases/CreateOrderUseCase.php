<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Application\DTOs\CreateOrderData;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Entities\OrderItem;

final class CreateOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository
    ) {}

    public function execute(CreateOrderData $data): Order
    {
        $domainItems = [];
        foreach ($data->items as $itemData) {
            $domainItems[] = OrderItem::create(
                productId: $itemData->productId,
                productName: $itemData->productName,
                sku: $itemData->sku,
                price: $itemData->price,
                quantity: $itemData->quantity,
                productVariantId: $itemData->productVariantId,
                attributes: $itemData->attributes,
                id: $itemData->id
            );
        }

        $order = Order::create(
            customerId: $data->customerId,
            paymentMethod: $data->paymentMethod,
            items: $domainItems,
            taxAmount: $data->taxAmount,
            shippingAmount: $data->shippingAmount,
            discountAmount: $data->discountAmount,
            orderNumber: $data->orderNumber,
            currency: $data->currency ?? 'USD',
            shippingMethod: $data->shippingMethod,
            notes: $data->notes,
            customerNote: $data->customerNote,
            metadata: $data->metadata,
            id: $data->id
        );

        $this->repository->save($order);

        return $order;
    }
}
