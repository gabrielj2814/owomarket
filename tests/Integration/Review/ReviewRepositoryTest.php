<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Brand\Infrastructure\Eloquent\Models\Brand as EloquentBrand;
use Src\Category\Infrastructure\Eloquent\Models\Category as EloquentCategory;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as EloquentCustomer;
use Src\Product\Infrastructure\Eloquent\Models\Product as EloquentProduct;
use Src\Review\Application\DTOs\FilterReviewsCriteria;
use Src\Review\Domain\Entities\ProductReview;
use Src\Review\Domain\ValueObjects\Rating;
use Src\Review\Domain\ValueObjects\ReviewId;
use Src\Review\Infrastructure\Eloquent\Repositories\EloquentReviewRepository;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('categories')) {
        (require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php'))->up();
    }
    if (! Schema::hasTable('brands')) {
        (require base_path('database/migrations/tenant/2025_10_28_143000_create_brands.php'))->up();
    }
    if (! Schema::hasTable('products')) {
        (require base_path('database/migrations/tenant/2025_10_28_143038_create_products.php'))->up();
    }
    if (! Schema::hasTable('customers')) {
        (require base_path('database/migrations/tenant/2025_10_28_144201_create_customers.php'))->up();
    }
    if (! Schema::hasTable('orders')) {
        (require base_path('database/migrations/tenant/2025_10_28_144320_create_orders.php'))->up();
    }
    if (! Schema::hasTable('product_reviews')) {
        (require base_path('database/migrations/tenant/2025_10_28_144615_create_product_reviews.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Review Repo Test Store',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);

    tenancy()->initialize($this->tenant);

    $this->category = EloquentCategory::create([
        'name' => 'Electrónica',
        'slug' => 'electronica-'.bin2hex(random_bytes(4)),
        'is_active' => true,
    ]);

    $this->brand = EloquentBrand::create([
        'name' => 'Sony',
        'slug' => 'sony-'.bin2hex(random_bytes(4)),
        'is_active' => true,
    ]);

    $this->product = EloquentProduct::create([
        'id' => (string) Str::uuid(),
        'name' => 'Auriculares Sony WH-1000XM5',
        'slug' => 'auriculares-sony-wh-1000xm5-'.bin2hex(random_bytes(4)),
        'category_id' => $this->category->id,
        'brand_id' => $this->brand->id,
        'sku' => 'SONY-WH1000XM5-'.bin2hex(random_bytes(2)),
        'price' => 350.00,
        'is_visible' => true,
    ]);

    $this->customer = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Valeria Miranda',
        'email' => 'valeria@musica.cl',
        'is_active' => true,
    ]);

    $this->repository = new EloquentReviewRepository;
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('saves and retrieves product review in tenant database', function () {
    $reviewId = ReviewId::random();
    $review = new ProductReview(
        id: $reviewId,
        productId: $this->product->id,
        customerId: $this->customer->id,
        rating: Rating::fromInt(5),
        title: 'Sonido espectacular',
        comment: 'La cancelación de ruido es la mejor del mercado.',
        isApproved: true,
        isVerified: true
    );

    $this->repository->save($review);

    $found = $this->repository->findById($reviewId);
    expect($found)->not->toBeNull()
        ->and($found->id()->equals($reviewId))->toBeTrue()
        ->and($found->rating()->value())->toBe(5)
        ->and($found->title())->toBe('Sonido espectacular')
        ->and($found->isApproved())->toBeTrue();

    $foundByCustomer = $this->repository->findByCustomerAndProduct($this->customer->id, $this->product->id);
    expect($foundByCustomer)->not->toBeNull()
        ->and($foundByCustomer->id()->equals($reviewId))->toBeTrue();

    $approvedList = $this->repository->findByProductId($this->product->id, true);
    expect($approvedList)->toHaveCount(1);
});

it('filters reviews and calculates star rating breakdown correctly in tenant database', function () {
    // Review 1: 5 stars, approved
    $this->repository->save(new ProductReview(
        id: ReviewId::random(),
        productId: $this->product->id,
        customerId: $this->customer->id,
        rating: Rating::fromInt(5),
        title: 'Excelente',
        isApproved: true
    ));

    $customer2 = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Pedro Pascal',
        'email' => 'pedro@hollywood.com',
        'is_active' => true,
    ]);

    // Review 2: 4 stars, approved with response
    $review2 = new ProductReview(
        id: ReviewId::random(),
        productId: $this->product->id,
        customerId: $customer2->id,
        rating: Rating::fromInt(4),
        title: 'Muy bueno',
        response: 'Muchas gracias por preferirnos!',
        isApproved: true
    );
    $this->repository->save($review2);

    $customer3 = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Ignacio Gonzalez',
        'email' => 'ignacio@test.cl',
        'is_active' => true,
    ]);

    // Review 3: 1 star, pending approval
    $this->repository->save(new ProductReview(
        id: ReviewId::random(),
        productId: $this->product->id,
        customerId: $customer3->id,
        rating: Rating::fromInt(1),
        title: 'No me gustó',
        isApproved: false
    ));

    // Filter by approved = true
    $approvedResult = $this->repository->filter(new FilterReviewsCriteria(isApproved: true));
    expect($approvedResult->total)->toBe(2);

    // Filter by hasResponse = true
    $withResponseResult = $this->repository->filter(new FilterReviewsCriteria(hasResponse: true));
    expect($withResponseResult->total)->toBe(1);

    // Summary calculation (only approved reviews count: 5 and 4 -> avg 4.5)
    $summary = $this->repository->getRatingSummary($this->product->id);
    expect($summary->totalReviews)->toBe(2)
        ->and($summary->averageRating)->toBe(4.5)
        ->and($summary->starBreakdown[5])->toBe(1)
        ->and($summary->starBreakdown[4])->toBe(1)
        ->and($summary->starBreakdown[1])->toBe(0);

    // Delete review 2
    $this->repository->delete($review2->id());
    expect($this->repository->findById($review2->id()))->toBeNull();
});
