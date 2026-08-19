<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Infrastructure\Eloquent\Repositories\CouponRepository;

final class CouponServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CouponRepositoryInterface::class,
            CouponRepository::class
        );
    }

    public function boot(): void {}
}
