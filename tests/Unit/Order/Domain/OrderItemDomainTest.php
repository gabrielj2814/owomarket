<?php

declare(strict_types=1);

use Src\Order\Domain\Entities\OrderItem;
use Src\Order\Domain\ValueObjects\OrderItemId;

it('creates OrderItem entity with valid attributes and calculates total', function () {
    $item = OrderItem::create(
        productId: 'prod-uuid-1',
        productName: 'Camiseta de Algodón',
        sku: 'TSHIRT-BLK-L',
        price: 19.99,
        quantity: 3,
        productVariantId: 'var-uuid-1',
        attributes: ['color' => 'negro', 'talla' => 'L']
    );

    expect($item->id())->toBeInstanceOf(OrderItemId::class)
        ->and($item->productId())->toBe('prod-uuid-1')
        ->and($item->productName())->toBe('Camiseta de Algodón')
        ->and($item->sku())->toBe('TSHIRT-BLK-L')
        ->and($item->price()->amount())->toBe(19.99)
        ->and($item->quantity())->toBe(3)
        ->and($item->total()->amount())->toBe(59.97)
        ->and($item->productVariantId())->toBe('var-uuid-1')
        ->and($item->attributes())->toBe(['color' => 'negro', 'talla' => 'L']);

    $array = $item->toArray();
    expect($array['product_name'])->toBe('Camiseta de Algodón')
        ->and($array['total'])->toBe(59.97);
});

it('throws exception when OrderItem quantity is less than 1', function () {
    OrderItem::create(
        productId: 'prod-uuid-1',
        productName: 'Producto Inválido',
        sku: 'SKU-0',
        price: 10.0,
        quantity: 0
    );
})->throws(InvalidArgumentException::class);

it('throws exception when OrderItem productName is empty', function () {
    OrderItem::create(
        productId: 'prod-uuid-1',
        productName: '   ',
        sku: 'SKU-0',
        price: 10.0,
        quantity: 1
    );
})->throws(InvalidArgumentException::class);
