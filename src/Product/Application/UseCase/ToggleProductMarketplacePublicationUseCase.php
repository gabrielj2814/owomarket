<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Exception;
use Src\Product\Infrastructure\Eloquent\Models\Product as EloquentProduct;

final class ToggleProductMarketplacePublicationUseCase
{
    public function execute(string $productId, ?bool $isPublishedCentral = null): EloquentProduct
    {
        $product = EloquentProduct::with(['category', 'brand', 'images', 'variants.attributeValues'])->find($productId);

        if (! $product) {
            throw new Exception('Producto no encontrado.', 404);
        }

        $newStatus = $isPublishedCentral ?? (! (bool) $product->is_published_central);

        $product->is_published_central = $newStatus;
        if ($newStatus) {
            $product->published_to_central_at = now();
        }
        // La sincronización con el catálogo central la dispara `ProductObserver` desde el
        // evento `saved` (hallazgos E1 y E2). Antes se invocaba a mano aquí, y éste era el
        // único camino de toda la aplicación que se acordaba de hacerlo.
        $product->save();

        return $product->fresh(['category', 'brand', 'images', 'variants.attributeValues']);
    }
}
