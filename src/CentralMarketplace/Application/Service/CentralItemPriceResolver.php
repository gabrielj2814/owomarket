<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Application\Service;

use Exception;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;

/**
 * Resuelve el precio real de cada línea del carrito multi-tienda contra
 * `central_products` — nunca contra lo que envía el navegador (hallazgo B1).
 *
 * Antes, `CreateUnifiedCentralOrderUseCase` hacía:
 *
 *     foreach ($items as $item) {
 *         $subtotal += (float) ($item['price'] * (int) ($item['quantity'] ?? 1));
 *     }
 *
 * sin consultar nunca el precio real. El comprador interceptaba el POST del
 * checkout, enviaba "price": 0.01 para un producto de $500 y el pedido
 * central se creaba por $0,01, con su comisión proporcional.
 *
 * ¿Por qué `central_products` y no la base de cada inquilino? Porque es el
 * precio que el comprador vio en el listado del marketplace, y porque evita
 * inicializar la tenancy una vez por línea dentro del checkout. La contra
 * conocida es que ese catálogo puede quedar desactualizado —hallazgos E1 y
 * E2, que siguen abiertos— pero eso es un problema de sincronización, no de
 * confianza en el cliente: con este cambio el navegador ya no fija precios.
 */
final class CentralItemPriceResolver
{
    /**
     * @param  array<string, mixed>  $item  Línea tal como llega del navegador.
     * @return array{tenant_id: string, product_id: string, central_product_id: string, name: string, sku: string|null, price: float, quantity: int, available_stock: int, attributes: array<string, mixed>|null}
     *
     * @throws Exception 422 si el producto no existe en el catálogo central o no está publicado
     */
    public function resolve(array $item): array
    {
        $tenantId = (string) ($item['tenant_id'] ?? '');
        $productId = (string) ($item['product_id'] ?? '');
        $quantity = max(1, (int) ($item['quantity'] ?? 1));

        if ($tenantId === '' || $productId === '') {
            throw new Exception('El carrito contiene una línea sin tienda o producto.', 422);
        }

        // El carrito central guarda `tenant_product_id` como product_id, con
        // reserva al id del propio CentralProduct (ver CentralProductDetailPage.tsx:77).
        // Se busca por ambos, pero SIEMPRE acotado al tenant_id de la línea:
        // sin ese filtro, los slugs e ids repetidos entre tiendas permitirían
        // cobrar el producto de una tienda con el precio de otra (hallazgo E3).
        $product = CentralProduct::where('tenant_id', $tenantId)
            ->where(function ($q) use ($productId) {
                $q->where('tenant_product_id', $productId)
                    ->orWhere('id', $productId);
            })
            ->first();

        if (! $product) {
            throw new Exception('Uno de los productos de tu carrito ya no está disponible.', 422);
        }

        if (($product->is_visible ?? true) === false) {
            throw new Exception(
                sprintf('El producto «%s» ya no está a la venta.', (string) $product->name),
                422
            );
        }

        $variante = $this->resolverVariante($product, $item);

        return [
            'tenant_id' => (string) $product->tenant_id,
            // Se conserva el identificador del inquilino, que es el que
            // DispatchCentralOrderToTenantsUseCase usa para crear el pedido
            // en la tienda.
            'product_id' => (string) ($product->tenant_product_id ?: $product->id),
            // El id central se devuelve aparte para que la revalidacion del carrito pueda
            // enlazar cada linea con su ficha (hallazgo N31).
            'central_product_id' => (string) $product->id,
            // Hallazgo N36: el id de la variante es el de la tienda, no uno central: las
            // variantes viven en la base del inquilino y es alli donde `StockReserver`
            // tiene que descontar.
            'variant_id' => $variante !== null ? (string) $variante['id'] : null,
            'name' => (string) $product->name,
            // Precio, SKU y stock salen de la variante cuando la hay. El del padre no
            // sirve: su `quantity` no lo mantiene nadie en un producto con variantes.
            'sku' => $variante !== null
                ? ($variante['sku'] !== null ? (string) $variante['sku'] : null)
                : ($product->sku !== null ? (string) $product->sku : null),
            'price' => (float) ($variante['price'] ?? $product->price),
            'quantity' => $quantity,
            'available_stock' => $variante !== null
                ? (int) ($variante['quantity'] ?? 0)
                : (int) ($product->quantity ?? 0),
            'attributes' => $variante !== null && $variante['attributes'] !== []
                ? $variante['attributes']
                : (isset($item['attributes']) && is_array($item['attributes']) ? $item['attributes'] : null),
        ];
    }

    /**
     * Resuelve la variante de una linea contra el catalogo central (hallazgo N36).
     *
     * `central_products.variants` ya traia id, sku, precio, stock y atributos desde la
     * Fase 2.2; lo que faltaba era usarlos. Se resuelve aqui y no en el controlador para
     * que la revalidacion del carrito (N31) lo herede sin tocar nada: delega en este
     * mismo metodo.
     *
     * **Un producto con variantes exige elegir una.** Antes, no elegir vendia el padre en
     * silencio: el comerciante recibia un pedido sin saber que talla enviar, y el stock se
     * descontaba de un numero que nadie mantiene. Rechazarlo es preferible a aceptar un
     * pedido que no se puede servir; los carritos viejos guardados en el navegador se
     * marcan como no disponibles con este motivo, que es justo para lo que existe la
     * revalidacion.
     *
     * @param  array<string, mixed>  $item
     * @return array{id: string, sku: string|null, price: float, quantity: int, attributes: array<string, mixed>}|null
     *
     * @throws Exception 422
     */
    private function resolverVariante(CentralProduct $product, array $item): ?array
    {
        $variantes = is_array($product->variants) ? $product->variants : [];
        $variantId = isset($item['variant_id']) && $item['variant_id'] !== ''
            ? (string) $item['variant_id']
            : null;

        if ($variantes === []) {
            if ($variantId !== null) {
                throw new Exception(
                    sprintf('El producto «%s» ya no se vende por variantes.', (string) $product->name),
                    422
                );
            }

            return null;
        }

        if ($variantId === null) {
            throw new Exception(
                sprintf('Tienes que elegir una opcion de «%s» antes de comprarlo.', (string) $product->name),
                422
            );
        }

        foreach ($variantes as $variante) {
            if ((string) ($variante['id'] ?? '') !== $variantId) {
                continue;
            }

            return [
                'id' => (string) $variante['id'],
                'sku' => isset($variante['sku']) && $variante['sku'] !== null ? (string) $variante['sku'] : null,
                'price' => (float) ($variante['price'] ?? $product->price),
                'quantity' => (int) ($variante['quantity'] ?? 0),
                'attributes' => isset($variante['attributes']) && is_array($variante['attributes'])
                    ? $variante['attributes']
                    : [],
            ];
        }

        throw new Exception(
            sprintf('La opcion elegida de «%s» ya no esta disponible.', (string) $product->name),
            422
        );
    }
}
