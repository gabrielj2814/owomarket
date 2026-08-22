<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Product\Application\Contracts\ProductMediaStorageInterface;
use Src\Product\Application\Contracts\ProductRepositoryInterface;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Product\Infrastructure\Eloquent\Observers\ProductObserver;
use Src\Product\Infrastructure\Eloquent\Repositories\ProductRepository;
use Src\Product\Infrastructure\Services\LaravelProductMediaStorageService;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductMediaStorageInterface::class, LaravelProductMediaStorageService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Hallazgos E1 y E2: la sincronización con `central_products` dependía de que cada
        // camino se acordara de invocarla, y sólo lo hacía el botón de publicación. Desde
        // aquí la disparan los eventos del modelo, por los que sí pasan todos los caminos.
        Product::observe(ProductObserver::class);

        if ($this->app->runningInConsole()) {
            // N24: el observador solo reacciona a guardados nuevos, asi que reparar un
            // catalogo ya desincronizado pedia un `tinker` a mano.
            $this->commands([
                \Src\Product\Infrastructure\Console\Commands\ResyncCentralCatalogCommand::class,
            ]);
        }
    }
}
