<?php

declare(strict_types=1);

namespace Src\Marketplace\Application\Service;

use Exception;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Product\Infrastructure\Eloquent\Models\ProductVariant;

/**
 * Descuenta stock de forma segura frente a compras simultáneas (hallazgo C1).
 *
 * El código anterior tenía tres bugs en nueve líneas:
 *
 *     try {
 *         if ($varId) {
 *             $variant = ProductVariant::find($varId);
 *             if ($variant && $variant->quantity >= $qty) { $variant->decrement('quantity', $qty); }
 *         }
 *         $product = Product::find($pId);
 *         if ($product && $product->quantity >= $qty) { $product->decrement('quantity', $qty); }
 *     } catch (\Throwable) {
 *     }
 *
 *   1. Si no había stock, el pedido se creaba igual: el `if` simplemente no
 *      descontaba y nadie se enteraba.
 *   2. Con variante se descontaba DOS veces (de la variante y del padre).
 *   3. Cualquier error quedaba tragado por el `catch` vacío.
 *
 * Los puntos 2 y 3 se corrigieron en la Fase 0.4. Este servicio cierra el 1 y
 * la carrera que quedaba: leer, comprobar y descontar eran tres pasos sin
 * bloqueo, así que dos pedidos simultáneos sobre la última unidad leían ambos
 * `quantity = 1`, ambos descontaban, y el stock terminaba en −1 con dos
 * pedidos que no se podían servir.
 *
 * **Debe invocarse dentro de una transacción** (lo hace el checkout): el
 * `lockForUpdate()` sólo tiene efecto dentro de una.
 */
final class StockReserver
{
    /**
     * Descuenta $quantity del producto o de su variante, bloqueando la fila.
     *
     * @throws Exception 409 si no hay existencias suficientes
     */
    public function reserve(string $productId, ?string $variantId, int $quantity, string $productName): void
    {
        if ($variantId !== null) {
            $variant = ProductVariant::where('id', $variantId)->lockForUpdate()->first();

            if (! $variant) {
                throw new Exception('La variante seleccionada ya no está disponible.', 409);
            }

            $this->assertEnough((int) ($variant->quantity ?? 0), $quantity, $productName);

            $variant->quantity = (int) $variant->quantity - $quantity;
            $variant->save();

            return;
        }

        $product = Product::where('id', $productId)->lockForUpdate()->first();

        if (! $product) {
            throw new Exception('El producto ya no está disponible.', 409);
        }

        // Un producto sin control de inventario se vende sin descontar nada.
        if (($product->track_quantity ?? true) === false) {
            return;
        }

        $this->assertEnough((int) ($product->quantity ?? 0), $quantity, $productName);

        $product->quantity = (int) $product->quantity - $quantity;
        $product->save();
    }

    /**
     * Devuelve stock al cancelar. No lleva bloqueo porque sumar no puede dejar
     * el inventario en negativo.
     */
    public function release(string $productId, ?string $variantId, int $quantity): void
    {
        if ($variantId !== null) {
            ProductVariant::where('id', $variantId)->increment('quantity', $quantity);

            return;
        }

        // Hallazgo E2: `increment()` escribe con el query builder y **no dispara eventos de
        // modelo**, así que la reposición no llegaba al catálogo central. Se guarda sobre
        // el modelo para que `ProductObserver` sincronice el stock devuelto.
        $product = Product::where('id', $productId)->first();

        if ($product) {
            $product->quantity = (int) $product->quantity + $quantity;
            $product->save();
        }
    }

    /**
     * @throws Exception 409
     */
    private function assertEnough(int $available, int $requested, string $productName): void
    {
        if ($available < $requested) {
            throw new Exception(
                sprintf(
                    'No hay existencias suficientes de «%s». Disponible: %d, solicitado: %d.',
                    $productName,
                    max(0, $available),
                    $requested
                ),
                409
            );
        }
    }
}
