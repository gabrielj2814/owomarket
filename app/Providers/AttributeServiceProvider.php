<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Infrastructure\Eloquent\Repositories\AttributeRepository;

final class AttributeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AttributeRepositoryInterface::class,
            AttributeRepository::class
        );
    }

    public function boot(): void
    {
    }
}
