<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Infrastructure\Eloquent\Repositories\EloquentOrderRepository;

final class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            EloquentOrderRepository::class
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Vive aqui y no en un proveedor propio de Monetization porque registrar un
            // comando no justifica un fichero nuevo mas una linea en `bootstrap/providers.php`.
            // Es de pedidos entregados, asi que este es su sitio natural.
            $this->commands([
                \Src\Monetization\Infrastructure\Console\Commands\ReleaseUnconfirmedDeliveriesCommand::class,
                \Src\CentralCustomer\Infrastructure\Console\Commands\AutoResolveStaleReturnsCommand::class,
            ]);
        }
    }
}
