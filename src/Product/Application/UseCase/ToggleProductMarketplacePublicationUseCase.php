<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Exception;
use Src\Product\Infrastructure\Eloquent\Models\Product as EloquentProduct;

final class ToggleProductMarketplacePublicationUseCase
{
    public function __construct(
        private readonly SyncProductToCentralMarketplaceUseCase $syncUseCase
    ) {}

    /**
     * @param string $productId
     * @param bool|null $isPublishedCentral
     * @return EloquentProduct
     */
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
        $product->save();

        // Sync with Central Marketplace Catalog
        $this->syncUseCase->execute($product);

        return $product->fresh(['category', 'brand', 'images', 'variants.attributeValues']);
    }
}
