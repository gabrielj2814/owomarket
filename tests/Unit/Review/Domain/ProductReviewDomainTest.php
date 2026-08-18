<?php

declare(strict_types=1);

use Src\Review\Domain\Entities\ProductReview;
use Src\Review\Domain\ValueObjects\Rating;
use Src\Review\Domain\ValueObjects\ReviewId;

it('ProductReview creates instance cleanly with default and custom values', function () {
    $review = ProductReview::create(
        productId: 'prod-uuid-1',
        customerId: 'cust-uuid-1',
        rating: Rating::fromInt(5),
        orderId: 'ord-uuid-1',
        title: 'Excelente calidad',
        comment: 'Muy contento con la compra del producto.',
        isApproved: false,
        isVerified: true
    );

    expect($review->id())->toBeInstanceOf(ReviewId::class)
        ->and($review->productId())->toBe('prod-uuid-1')
        ->and($review->customerId())->toBe('cust-uuid-1')
        ->and($review->orderId())->toBe('ord-uuid-1')
        ->and($review->rating()->value())->toBe(5)
        ->and($review->title())->toBe('Excelente calidad')
        ->and($review->comment())->toBe('Muy contento con la compra del producto.')
        ->and($review->isApproved())->toBeFalse()
        ->and($review->isVerified())->toBeTrue()
        ->and($review->response())->toBeNull()
        ->and($review->hasResponse())->toBeFalse();

    $array = $review->toArray();
    expect($array['product_id'])->toBe('prod-uuid-1')
        ->and($array['rating'])->toBe(5)
        ->and($array['is_approved'])->toBeFalse();
});

it('ProductReview approves and rejects moderation state', function () {
    $review = ProductReview::create(
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: Rating::fromInt(4),
        isApproved: false
    );

    expect($review->isApproved())->toBeFalse();

    $review->approve();
    expect($review->isApproved())->toBeTrue();

    $review->reject();
    expect($review->isApproved())->toBeFalse();
});

it('ProductReview adds, modifies and clears admin responses', function () {
    $review = ProductReview::create(
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: Rating::fromInt(2),
        comment: 'Llegó un poco tarde.'
    );

    expect($review->hasResponse())->toBeFalse();

    $review->respond('Lamentamos el inconveniente, revisaremos con el courier.');
    expect($review->hasResponse())->toBeTrue()
        ->and($review->response())->toBe('Lamentamos el inconveniente, revisaremos con el courier.')
        ->and($review->respondedAt())->not->toBeNull();

    $review->clearResponse();
    expect($review->hasResponse())->toBeFalse()
        ->and($review->response())->toBeNull()
        ->and($review->respondedAt())->toBeNull();
});

it('ProductReview updates rating, title, comment and verified status', function () {
    $review = ProductReview::create(
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: Rating::fromInt(3),
        title: 'Regular',
        comment: 'Esperaba más.'
    );

    $review->updateContent(
        rating: Rating::fromInt(5),
        title: 'Actualizado: Me encantó',
        comment: 'El soporte resolvió mis dudas y funciona perfecto.'
    );

    expect($review->rating()->value())->toBe(5)
        ->and($review->title())->toBe('Actualizado: Me encantó')
        ->and($review->comment())->toBe('El soporte resolvió mis dudas y funciona perfecto.');

    $review->markAsVerified(true);
    expect($review->isVerified())->toBeTrue();
});
