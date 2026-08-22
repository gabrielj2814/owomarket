<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Src\Product\Application\UseCase\SyncProductToCentralMarketplaceUseCase;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Throwable;

/**
 * Propaga un producto de tienda al catálogo del marketplace central, en segundo plano
 * y con reintentos (hallazgo N25).
 *
 * **Por qué en cola.** La sincronización escribía en la base central dentro de la misma
 * petición que había modificado el producto —incluida la del checkout, dentro de su
 * transacción—. Si el marketplace no respondía, la fila quedaba desincronizada y lo
 * único que quedaba era una línea de log. Y desde la Fase 0.4 el checkout central toma
 * los precios de `central_products`, así que una fila desincronizada es dinero mal
 * cobrado, no un detalle cosmético.
 *
 * **La tenancy no viaja sola.** El job se ejecuta en un worker sin contexto de tienda,
 * así que el `tenant_id` se serializa explícitamente y se inicializa aquí. Sin esto,
 * `Product::find()` buscaría en la base central, donde ese producto no existe.
 *
 * **Se guarda el id, no el modelo.** Entre el encolado y la ejecución el producto puede
 * haber cambiado otra vez; lo que hay que sincronizar es su estado actual, no el que
 * tenía cuando se disparó el evento. Y si lo borraron, no hay nada que sincronizar.
 */
final class SyncProductToCentralCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 120, 300, 900];
    }

    /**
     * @param  string  $accion  'sync' publica o actualiza; 'withdraw' retira del catálogo.
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly string $productId,
        private readonly string $accion
    ) {}

    public function handle(SyncProductToCentralMarketplaceUseCase $useCase): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            Log::warning('SyncProductToCentralCatalogJob: la tienda ya no existe.', [
                'tenant_id' => $this->tenantId,
                'product_id' => $this->productId,
            ]);

            return;
        }

        // Se recuerda el contexto de tienda previo para devolverlo al salir. Con la cola
        // en modo `sync` el job corre DENTRO de la petición que lo encoló, así que un
        // `tenancy()->end()` a secas desmontaría la tienda de quien nos llamó y las
        // escrituras siguientes acabarían en la base central.
        $tenantPrevio = tenant();

        try {
            tenancy()->initialize($tenant);

            // `withTrashed` porque `withdraw` corre justo después de un borrado suave:
            // sin esto el producto sería invisible y no habría a quién retirar.
            $product = Product::withTrashed()->find($this->productId);

            if (! $product) {
                Log::warning('SyncProductToCentralCatalogJob: el producto ya no existe.', [
                    'tenant_id' => $this->tenantId,
                    'product_id' => $this->productId,
                ]);

                return;
            }

            $this->accion === 'withdraw'
                ? $useCase->withdraw($product)
                : $useCase->execute($product);
        } finally {
            if ($tenantPrevio !== null) {
                tenancy()->initialize($tenantPrevio);
            } elseif (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    /**
     * Agotados los intentos, la fila del catálogo central se queda descuadrada. Tiene que
     * quedar rastro de qué producto y de qué tienda, que es lo que necesita
     * `catalog:resync` para repararlo (hallazgo N24).
     */
    public function failed(Throwable $e): void
    {
        Log::error('No se pudo sincronizar el producto con el catálogo central tras agotar los reintentos.', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'accion' => $this->accion,
            'error' => $e->getMessage(),
        ]);
    }
}
