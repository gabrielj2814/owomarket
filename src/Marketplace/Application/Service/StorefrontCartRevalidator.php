<?php

declare(strict_types=1);

namespace Src\Marketplace\Application\Service;

use Throwable;

/**
 * Contrasta el carrito que trae el navegador con lo que dice la base de la tienda
 * (hallazgo G4).
 *
 * El carrito vive en `localStorage` con el precio y la cantidad congelados el día en que
 * el comprador añadió cada producto. Nadie los revalidaba nunca, así que el cliente podía
 * pasar días viendo un precio que ya no existe y llegar al checkout con un total que no
 * es el que se le va a cobrar — desde la Fase 0.4 el servidor resuelve los precios por su
 * cuenta e ignora los del navegador, así que la diferencia se la encuentra al pagar.
 *
 * Este servicio no decide nada: sólo dice **qué ha cambiado**, para que el carrito pueda
 * corregirse y avisar. Las líneas que ya no se pueden servir se marcan en lugar de hacer
 * fallar la petición entera: el comprador tiene que poder ver cuál es y quitarla.
 */
final class StorefrontCartRevalidator
{
    public function __construct(
        private readonly StorefrontItemPriceResolver $priceResolver
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $items  Líneas tal como las envía el navegador.
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
                // Producto borrado, oculto, o variante que ya no le corresponde.
                $lines[] = [
                    'product_id' => (string) ($item['product_id'] ?? ''),
                    'variant_id' => $item['variant_id'] ?? null,
                    'available' => false,
                    'reason' => $e->getMessage(),
                ];
                $hasChanges = true;

                continue;
            }

            $stock = $resolved['available_stock'];
            $availableQuantity = $stock === null
                ? $submittedQuantity
                : min($submittedQuantity, max(0, $stock));

            $priceChanged = $submittedPrice !== null
                && abs($submittedPrice - $resolved['price']) >= 0.01;
            $quantityReduced = $availableQuantity < $submittedQuantity;
            $outOfStock = $availableQuantity === 0;

            if ($priceChanged || $quantityReduced) {
                $hasChanges = true;
            }

            $lines[] = [
                'product_id' => $resolved['product_id'],
                'variant_id' => $resolved['variant_id'],
                'available' => ! $outOfStock,
                'name' => $resolved['name'],
                'sku' => $resolved['sku'],
                'price' => $resolved['price'],
                'quantity' => $availableQuantity,
                'available_stock' => $stock,
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
