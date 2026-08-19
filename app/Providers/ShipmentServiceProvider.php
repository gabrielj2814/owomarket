<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;
use Src\Shipment\Infrastructure\Eloquent\Repositories\EloquentShipmentRepository;

final class ShipmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ShipmentRepositoryInterface::class,
            EloquentShipmentRepository::class
        );
    }

    public function boot(): void {}
}
