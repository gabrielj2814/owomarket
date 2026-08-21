<?php

declare(strict_types=1);

namespace Src\Marketplace\Application\Service;

use Exception;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Product\Infrastructure\Eloquent\Models\ProductVariant;

/**
 * Resuelve el precio real de cada línea del carrito contra la base de datos
 * del inquilino — nunca contra lo que envía el navegador (hallazgo B1).
 *
 * Antes, `CreateStorefrontOrderPOSTController` calculaba el subtotal así:
 *
 *     $price = (float) $item['price'];        // ← del request
 *     $calculatedSubtotal += ($price * $qty);
 *
 * y la única validación era `numeric|min:0`. Interceptando el POST del
 * checkout y enviando "price": 0.01 para un producto de $500, se creaba el
 * pedido por $0,01, se registraba un `payment` de $0,01 y una comisión de
 * $0,0008 — y la tienda despachaba un producto de $500.
 *
 * Este servicio debe usarse con la tenancy ya inicializada (el checkout del
 * storefront corre dentro del grupo de `routes/tenant.php`), de modo que
 * `Product` y `ProductVariant` resuelven contra la base del inquilino.
 */
final class StorefrontItemPriceResolver
{
    /**
     * @param  array<string, mixed>  $item  Línea tal como llega del navegador.
     * @return array{product_id: string, variant_id: string|null, name: string, sku: string, price: float, quantity: int, available_stock: int|null, tracks_stock: bool}
     *
     * @throws Exception 422 si el producto no existe, no está visible o la variante no le pertenece
     */
    public function resolve(array $item): array
    {
        $productId = (string) ($item['product_id'] ?? '');
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $variantId = isset($item['variant_id']) && $item['variant_id'] !== ''
            ? (string) $item['variant_id']
            : null;

        $product = Product::find($productId);

        if (! $product) {
            throw new Exception('Uno de los productos de tu carrito ya no está disponible.', 422);
        }

        if (($product->is_visible ?? true) === false) {
            throw new Exception(
                sprintf('El producto «%s» ya no está a la venta.', (string) $product->name),
                422
            );
        }

        $variant = null;

        if ($variantId !== null) {
            $variant = ProductVariant::find($variantId);

            // La variante debe pertenecer a ESTE producto: si no se comprueba,
            // se podría pedir un producto caro con el precio de la variante
            // barata de otro producto.
            if (! $variant || (string) $variant->product_id !== (string) $product->id) {
                throw new Exception('La variante seleccionada no corresponde al producto.', 422);
            }
        }

        // El precio manda la variante si la hay y tiene precio propio; si no, el producto.
        $price = $variant !== null && $variant->price !== null
            ? (float) $variant->price
            : (float) $product->price;

        $tracksStock = (bool) ($product->track_quantity ?? true);
        $availableStock = $variant !== null
            ? (int) ($variant->quantity ?? 0)
            : (int) ($product->quantity ?? 0);

        return [
            'product_id' => (string) $product->id,
            'variant_id' => $variant?->id !== null ? (string) $variant->id : null,
            // Nombre y SKU también salen de la base: el navegador podía
            // renombrar la línea del pedido a su antojo.
            'name' => (string) $product->name,
            'sku' => (string) ($variant->sku ?? $product->sku ?? 'SKU-'.$product->id),
            'price' => $price,
            'quantity' => $quantity,
            'available_stock' => $tracksStock ? $availableStock : null,
            'tracks_stock' => $tracksStock,
        ];
    }
}
