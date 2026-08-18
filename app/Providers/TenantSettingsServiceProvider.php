<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Infrastructure\Eloquent\Repositories\EloquentTenantSettingsRepository;

final class TenantSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TenantSettingsRepositoryInterface::class,
            EloquentTenantSettingsRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
