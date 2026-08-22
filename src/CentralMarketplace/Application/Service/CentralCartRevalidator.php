<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Application\Service;

use Throwable;

/**
 * Contrasta el carrito del marketplace central con lo que dice `central_products`
 * (hallazgo N31).
 *
 * La Fase 3.2 hizo esto para el storefront de cada tienda y **dejó el carrito central
 * fuera**: seguía con el precio y la cantidad congelados el día en que el comprador añadió
 * cada producto. Desde la Fase 0.4 el checkout central resuelve los precios por su cuenta
 * e ignora los del navegador, así que la diferencia se la encontraba al pagar — y desde la
 * Fase 2.2 el catálogo central sí se mantiene al día, con lo que la diferencia es real.
 *
 * Mismo contrato que `StorefrontCartRevalidator`: no decide nada, sólo dice qué cambió, y
 * las líneas que ya no se pueden servir se marcan en vez de hacer fallar la petición.
 */
final class CentralCartRevalidator
{
    public function __construct(
        private readonly CentralItemPriceResolver $priceResolver
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{lines: array<int, array<string, mixed>>, has_changes: bool}
     */
    public function revalidate(array $items): array
    {
        $lines = [];
        $hasChanges = false;

        foreach ($items as $item) {
            $submittedPrice = isset($item['price']) ? (float) $item['price'] : null;
            $submittedQuantity = max(1, (int) ($item['quantity'] ?? 1));

            try {
                $resolved = $this->priceResolver->resolve($item);
            } catch (Throwable $e) {
                $lines[] = [
                    'tenant_id' => (string) ($item['tenant_id'] ?? ''),
                    'product_id' => (string) ($item['product_id'] ?? ''),
                    'available' => false,
                    'reason' => $e->getMessage(),
                ];
                $hasChanges = true;

                continue;
            }

            $availableQuantity = min($submittedQuantity, max(0, $resolved['available_stock']));

            $priceChanged = $submittedPrice !== null
                && abs($submittedPrice - $resolved['price']) >= 0.01;
            $quantityReduced = $availableQuantity < $submittedQuantity;
            $outOfStock = $availableQuantity === 0;

            if ($priceChanged || $quantityReduced) {
                $hasChanges = true;
            }

            $lines[] = [
                'tenant_id' => $resolved['tenant_id'],
                'product_id' => $resolved['product_id'],
                'central_product_id' => $resolved['central_product_id'],
                'available' => ! $outOfStock,
                'name' => $resolved['name'],
                'sku' => $resolved['sku'],
                'price' => $resolved['price'],
                'quantity' => $availableQuantity,
                'available_stock' => $resolved['available_stock'],
                'price_changed' => $priceChanged,
                'previous_price' => $priceChanged ? $submittedPrice : null,
                'quantity_reduced' => $quantityReduced,
                'reason' => $outOfStock
                    ? sprintf('«%s» se ha agotado.', $resolved['name'])
                    : null,
            ];
        }

        return [
            'lines' => $lines,
            'has_changes' => $hasChanges,
        ];
    }
}
