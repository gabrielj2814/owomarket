<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Eloquent\Observers;

use Illuminate\Support\Facades\Log;
use Src\Product\Application\UseCase\SyncProductToCentralMarketplaceUseCase;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Throwable;

/**
 * Mantiene `central_products` al día con lo que pasa en el catálogo de cada tienda
 * (hallazgos E1 y E2).
 *
 * Antes, la sincronización dependía de que cada camino se acordara de invocarla, y sólo
 * uno lo hacía: el botón de «publicar en el marketplace». El resultado era un catálogo
 * central congelado — precios viejos, productos borrados que seguían comprándose y stock
 * que nunca bajaba con las ventas. **Desde la Fase 0.4 el checkout central toma los
 * precios de `central_products`, así que eso ya no era cosmético: era dinero mal cobrado.**
 *
 * Los eventos de modelo son el único punto por el que pasan todos los caminos, así que
 * es imposible añadir un camino nuevo y olvidarse de sincronizar.
 *
 * **Requisito:** los eventos de Eloquent sólo se disparan sobre instancias de modelo. Las
 * escrituras con el query builder (`Product::where(...)->update(...)`) los saltan por
 * completo, por eso `ProductRepository` y `StockReserver` pasaron a operar sobre modelos.
 */
final class ProductObserver
{
    public function __construct(
        private readonly SyncProductToCentralMarketplaceUseCase $syncUseCase
    ) {}

    public function saved(Product $product): void
    {
        $this->sync($product, fn () => $this->syncUseCase->execute($product), 'sincronizar');
    }

    public function deleted(Product $product): void
    {
        $this->sync($product, fn () => $this->syncUseCase->withdraw($product), 'retirar');
    }

    public function restored(Product $product): void
    {
        $this->sync($product, fn () => $this->syncUseCase->execute($product), 'restaurar');
    }

    /**
     * El catálogo central vive en otra conexión, así que un fallo suyo **no** revierte la
     * escritura de la tienda: abortar una venta porque el marketplace no responde sería
     * peor que la desincronización que causa.
     *
     * Pero se registra como `error`, no en silencio como hacía el `catch (\Throwable) {}`
     * del antiguo `updateStock()`: una fila desincronizada es dinero mal cobrado, y tiene
     * que quedar rastro de qué producto y de qué tienda quedó descuadrado.
     */
    private function sync(Product $product, callable $operation, string $accion): void
    {
        try {
            $operation();
        } catch (Throwable $e) {
            Log::error("No se pudo {$accion} el producto en el catálogo del marketplace central.", [
                'tenant_id' => tenant('id'),
                'product_id' => $product->id,
                'product_name' => $product->name,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
