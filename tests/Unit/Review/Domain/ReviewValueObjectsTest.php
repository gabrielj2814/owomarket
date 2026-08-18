<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;
use Src\Review\Domain\Exceptions\InvalidRatingException;
use Src\Review\Domain\ValueObjects\Rating;
use Src\Review\Domain\ValueObjects\ReviewId;

it('ReviewId generates valid UUID and compares equality', function () {
    $id = ReviewId::random();
    expect(Uuid::isValid($id->value()))->toBeTrue()
        ->and((string) $id)->toBe($id->value());

    $sameId = ReviewId::fromString($id->value());
    expect($id->equals($sameId))->toBeTrue();

    $otherId = ReviewId::random();
    expect($id->equals($otherId))->toBeFalse();
});

it('ReviewId throws exception on invalid UUID string', function () {
    new ReviewId('invalid-uuid-format');
})->throws(InvalidArgumentException::class);

it('Rating accepts integers between 1 and 5 and classifies sentiment', function () {
    $rating5 = Rating::fromInt(5);
    expect($rating5->value())->toBe(5)
        ->and($rating5->isPositive())->toBeTrue()
        ->and($rating5->isNeutral())->toBeFalse()
        ->and($rating5->isNegative())->toBeFalse()
        ->and((string) $rating5)->toBe('5');

    $rating3 = Rating::fromInt(3);
    expect($rating3->isNeutral())->toBeTrue()
        ->and($rating3->isPositive())->toBeFalse();

    $rating1 = Rating::fromInt(1);
    expect($rating1->isNegative())->toBeTrue()
        ->and($rating1->isPositive())->toBeFalse();
});

it('Rating throws InvalidRatingException on values outside 1-5', function () {
    Rating::fromInt(0);
})->throws(InvalidRatingException::class);

it('Rating throws InvalidRatingException on values greater than 5', function () {
    Rating::fromInt(6);
})->throws(InvalidRatingException::class);
