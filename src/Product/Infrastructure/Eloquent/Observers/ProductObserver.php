<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Eloquent\Observers;

use Illuminate\Support\Facades\Log;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Product\Infrastructure\Jobs\SyncProductToCentralCatalogJob;
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
 *
 * **Hallazgo N25 — el observer ya no sincroniza, encola.** La escritura en la base
 * central iba dentro de la misma petición que había tocado el producto, incluida la
 * transacción del checkout: si el marketplace no respondía, la fila quedaba descuadrada
 * y sólo quedaba una línea de log que nada reintentaba. Ahora el trabajo se delega a
 * `SyncProductToCentralCatalogJob`, que reintenta cinco veces con espera creciente.
 */
final class ProductObserver
{
    public function saved(Product $product): void
    {
        $this->encolar($product, 'sync');
    }

    public function deleted(Product $product): void
    {
        $this->encolar($product, 'withdraw');
    }

    public function restored(Product $product): void
    {
        $this->encolar($product, 'sync');
    }

    /**
     * Encolar tampoco puede tumbar la escritura de la tienda: si la conexión de la cola
     * falla, se registra y se sigue. Abortar una venta porque el marketplace no responde
     * sería peor que la desincronización que causa.
     *
     * El `tenant_id` se captura AQUÍ, que es donde todavía hay contexto de tienda: el
     * worker que ejecute el job no lo tendrá.
     */
    private function encolar(Product $product, string $accion): void
    {
        $tenantId = tenant('id');

        if ($tenantId === null) {
            // Fuera de una tienda no hay catálogo que sincronizar: es el caso de los
            // seeders centrales y de los tests que crean productos sin tenancy.
            return;
        }

        try {
            SyncProductToCentralCatalogJob::dispatch((string) $tenantId, (string) $product->id, $accion);
        } catch (Throwable $e) {
            Log::error('No se pudo encolar la sincronización del producto con el catálogo central.', [
                'tenant_id' => $tenantId,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'accion' => $accion,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
