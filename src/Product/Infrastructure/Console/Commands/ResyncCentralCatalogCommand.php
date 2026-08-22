<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Throwable;

/**
 * Re-sincroniza el catalogo central a partir de los productos de cada tienda (N24).
 *
 * La Fase 2.2 puso la sincronizacion en `ProductObserver`, pero **solo reacciona a
 * guardados nuevos**: los productos que ya estaban publicados siguen con lo que hubiera en
 * `central_products` el dia de su publicacion. Reparar eso pedia un `tinker` a mano.
 *
 * Es idempotente: `touch()` dispara el observador sin cambiar ningun dato del producto.
 *
 * **Desde N25 el observador encola en vez de sincronizar**, asi que este comando deja el
 * trabajo preparado y quien lo termina es el worker. Con la cola parada no se re-sincroniza
 * nada, por eso el resumen habla de productos ENCOLADOS y no de re-sincronizados: decir lo
 * segundo seria mentir sobre algo que aun no ha pasado.
 */
final class ResyncCentralCatalogCommand extends Command
{
    protected $signature = 'catalog:resync {--tenant= : Slug o id de una tienda concreta}';

    protected $description = 'Vuelve a proyectar los productos publicados sobre el catalogo del marketplace central';

    public function handle(): int
    {
        $tenants = Tenant::query()
            ->when($this->option('tenant'), fn ($q, $t) => $q->where('id', $t)->orWhere('slug', $t))
            ->get();

        if ($tenants->isEmpty()) {
            $this->error('No se encontró ninguna tienda con ese criterio.');

            return self::FAILURE;
        }

        $total = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $publicados = Product::where('is_published_central', true)->get();

                foreach ($publicados as $producto) {
                    $producto->touch();
                    $total++;
                }

                $this->line("  {$tenant->name}: {$publicados->count()} productos");
            } catch (Throwable $e) {
                $this->error("  {$tenant->name}: {$e->getMessage()}");
            } finally {
                tenancy()->end();
            }
        }

        $this->info("✅ {$total} productos encolados para re-sincronizar en {$tenants->count()} tienda(s).");
        $this->line('   Los procesa el worker de colas; sin worker corriendo no se aplicaran.');

        return self::SUCCESS;
    }
}
