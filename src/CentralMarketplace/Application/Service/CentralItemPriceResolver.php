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
     * @return array{tenant_id: string, product_id: string, name: string, sku: string|null, price: float, quantity: int, attributes: array<string, mixed>|null}
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

        return [
            'tenant_id' => (string) $product->tenant_id,
            // Se conserva el identificador del inquilino, que es el que
            // DispatchCentralOrderToTenantsUseCase usa para crear el pedido
            // en la tienda.
            'product_id' => (string) ($product->tenant_product_id ?: $product->id),
            'name' => (string) $product->name,
            'sku' => $product->sku !== null ? (string) $product->sku : null,
            'price' => (float) $product->price,
            'quantity' => $quantity,
            'attributes' => isset($item['attributes']) && is_array($item['attributes'])
                ? $item['attributes']
                : null,
        ];
    }
}
