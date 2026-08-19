<?php

declare(strict_types=1);

use Src\Review\Application\DTOs\CreateReviewData;
use Src\Review\Application\DTOs\FilterReviewsCriteria;
use Src\Review\Application\DTOs\ModerateReviewData;
use Src\Review\Application\DTOs\PaginatedReviewResult;
use Src\Review\Application\DTOs\ProductRatingSummaryData;
use Src\Review\Application\DTOs\RespondReviewData;
use Src\Review\Application\DTOs\UpdateReviewData;
use Src\Review\Application\Repositories\ReviewRepositoryInterface;
use Src\Review\Application\UseCases\ConsultReviewByIdUseCase;
use Src\Review\Application\UseCases\CreateProductReviewUseCase;
use Src\Review\Application\UseCases\DeleteProductReviewUseCase;
use Src\Review\Application\UseCases\FilterReviewsUseCase;
use Src\Review\Application\UseCases\GetProductRatingSummaryUseCase;
use Src\Review\Application\UseCases\ModerateReviewUseCase;
use Src\Review\Application\UseCases\RespondReviewUseCase;
use Src\Review\Application\UseCases\UpdateProductReviewUseCase;
use Src\Review\Domain\Entities\ProductReview;
use Src\Review\Domain\Exceptions\DuplicateReviewException;
use Src\Review\Domain\Exceptions\ReviewNotFoundException;
use Src\Review\Domain\ValueObjects\Rating;
use Src\Review\Domain\ValueObjects\ReviewId;

beforeEach(function () {
    $this->repository = Mockery::mock(ReviewRepositoryInterface::class);
});

afterEach(function () {
    Mockery::close();
});

it('CreateProductReviewUseCase creates and saves review', function () {
    $this->repository->shouldReceive('findByCustomerAndProduct')
        ->once()
        ->with('cust-1', 'prod-1')
        ->andReturnNull();

    $this->repository->shouldReceive('save')
        ->once()
        ->with(Mockery::type(ProductReview::class));

    $useCase = new CreateProductReviewUseCase($this->repository);
    $data = new CreateReviewData(
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: 5,
        orderId: 'ord-1',
        title: 'Maravilloso',
        comment: 'Super recomendado.'
    );

    $review = $useCase->execute($data);

    expect($review->productId())->toBe('prod-1')
        ->and($review->customerId())->toBe('cust-1')
        ->and($review->rating()->value())->toBe(5)
        ->and($review->isVerified())->toBeTrue();
});

it('CreateProductReviewUseCase throws DuplicateReviewException when duplicate review exists', function () {
    $existing = ProductReview::create(
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: Rating::fromInt(4)
    );

    $this->repository->shouldReceive('findByCustomerAndProduct')
        ->once()
        ->with('cust-1', 'prod-1')
        ->andReturn($existing);

    $useCase = new CreateProductReviewUseCase($this->repository);
    $data = new CreateReviewData(
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: 5
    );

    $useCase->execute($data);
})->throws(DuplicateReviewException::class);

it('ModerateReviewUseCase approves or rejects review', function () {
    $reviewId = ReviewId::random();
    $review = new ProductReview(
        id: $reviewId,
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: Rating::fromInt(5),
        isApproved: false
    );

    $this->repository->shouldReceive('findById')
        ->twice()
        ->with(Mockery::on(fn (ReviewId $id) => $id->equals($reviewId)))
        ->andReturn($review);

    $this->repository->shouldReceive('save')
        ->twice()
        ->with($review);

    $useCase = new ModerateReviewUseCase($this->repository);

    // 1. Approve
    $approved = $useCase->execute(new ModerateReviewData(id: $reviewId->value(), isApproved: true));
    expect($approved->isApproved())->toBeTrue();

    // 2. Reject
    $rejected = $useCase->execute(new ModerateReviewData(id: $reviewId->value(), isApproved: false));
    expect($rejected->isApproved())->toBeFalse();
});

it('RespondReviewUseCase adds and clears merchant response', function () {
    $reviewId = ReviewId::random();
    $review = new ProductReview(
        id: $reviewId,
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: Rating::fromInt(3)
    );

    $this->repository->shouldReceive('findById')
        ->twice()
        ->with(Mockery::on(fn (ReviewId $id) => $id->equals($reviewId)))
        ->andReturn($review);

    $this->repository->shouldReceive('save')
        ->twice()
        ->with($review);

    $useCase = new RespondReviewUseCase($this->repository);

    $responded = $useCase->execute(new RespondReviewData(id: $reviewId->value(), response: 'Gracias por tu feedback!'));
    expect($responded->hasResponse())->toBeTrue()
        ->and($responded->response())->toBe('Gracias por tu feedback!');

    $cleared = $useCase->execute(new RespondReviewData(id: $reviewId->value(), response: ''));
    expect($cleared->hasResponse())->toBeFalse();
});

it('UpdateProductReviewUseCase updates rating and texts', function () {
    $reviewId = ReviewId::random();
    $review = new ProductReview(
        id: $reviewId,
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: Rating::fromInt(2),
        title: 'Malo',
        comment: 'No funcionó'
    );

    $this->repository->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (ReviewId $id) => $id->equals($reviewId)))
        ->andReturn($review);

    $this->repository->shouldReceive('save')
        ->once()
        ->with($review);

    $useCase = new UpdateProductReviewUseCase($this->repository);
    $updated = $useCase->execute(new UpdateReviewData(
        id: $reviewId->value(),
        rating: 5,
        title: 'Corregido',
        comment: 'Ahora sí funciona!'
    ));

    expect($updated->rating()->value())->toBe(5)
        ->and($updated->title())->toBe('Corregido')
        ->and($updated->comment())->toBe('Ahora sí funciona!');
});

it('ConsultReviewByIdUseCase returns review or throws exception', function () {
    $reviewId = ReviewId::random();
    $review = new ProductReview(
        id: $reviewId,
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: Rating::fromInt(4)
    );

    $this->repository->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (ReviewId $id) => $id->equals($reviewId)))
        ->andReturn($review);

    $useCase = new ConsultReviewByIdUseCase($this->repository);
    $result = $useCase->execute($reviewId->value());

    expect($result->id()->equals($reviewId))->toBeTrue();
});

it('FilterReviewsUseCase and GetProductRatingSummaryUseCase delegate to repository', function () {
    $paginated = new PaginatedReviewResult([], 0, 15, 1, 1);
    $criteria = new FilterReviewsCriteria;

    $this->repository->shouldReceive('filter')
        ->once()
        ->with($criteria)
        ->andReturn($paginated);

    $filterUseCase = new FilterReviewsUseCase($this->repository);
    expect($filterUseCase->execute($criteria))->toBe($paginated);

    $summary = new ProductRatingSummaryData(
        productId: 'prod-1',
        totalReviews: 10,
        averageRating: 4.8,
        starBreakdown: [1 => 0, 2 => 0, 3 => 1, 4 => 2, 5 => 7]
    );

    $this->repository->shouldReceive('getRatingSummary')
        ->once()
        ->with('prod-1')
        ->andReturn($summary);

    $summaryUseCase = new GetProductRatingSummaryUseCase($this->repository);
    expect($summaryUseCase->execute('prod-1'))->toBe($summary);
});

it('DeleteProductReviewUseCase deletes review or throws exception if not found', function () {
    $reviewId = ReviewId::random();
    $review = new ProductReview(
        id: $reviewId,
        productId: 'prod-1',
        customerId: 'cust-1',
        rating: Rating::fromInt(4)
    );

    $this->repository->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (ReviewId $id) => $id->equals($reviewId)))
        ->andReturn($review);

    $this->repository->shouldReceive('delete')
        ->once()
        ->with(Mockery::on(fn (ReviewId $id) => $id->equals($reviewId)));

    $useCase = new DeleteProductReviewUseCase($this->repository);
    $useCase->execute($reviewId->value());
    expect(true)->toBeTrue();
});

it('ConsultReviewByIdUseCase throws ReviewNotFoundException when not found', function () {
    $reviewId = ReviewId::random();

    $this->repository->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (ReviewId $id) => $id->equals($reviewId)))
        ->andReturnNull();

    $useCase = new ConsultReviewByIdUseCase($this->repository);
    $useCase->execute($reviewId->value());
})->throws(ReviewNotFoundException::class);
