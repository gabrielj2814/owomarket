<?php

declare(strict_types=1);

namespace Src\Product\Application\Contracts;

use Src\Product\Application\DTOs\PaginatedProductsResult;
use Src\Product\Application\DTOs\ProductFilterCriteria;
use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\ValueObjects\ProductId;
use Src\Product\Domain\ValueObjects\ProductSku;
use Src\Product\Domain\ValueObjects\ProductSlug;

interface ProductRepositoryInterface
{
    public function save(Product $product): Product;

    public function findById(ProductId $id): ?Product;

    public function findBySlug(ProductSlug $slug): ?Product;

    public function findBySku(ProductSku $sku): ?Product;

    public function update(Product $product): Product;

    public function delete(ProductId $id): void;

    public function filter(ProductFilterCriteria $criteria): PaginatedProductsResult;

    public function toggleVisibility(ProductId $id, bool $isVisible): void;

    /**
     * Hallazgo PR2: `$variantId` no es un extra opcional, es lo que decide DONDE se escribe.
     * En un producto con variantes el `quantity` del padre no lo mantiene ni lo lee nadie
     * —`StockReserver` solo descuenta de la variante (N36)—, asi que escribir ahi era
     * aceptar la operacion y no aplicarla.
     */
    public function updateStock(ProductId $id, int $quantity, ?string $variantId = null): void;
}
