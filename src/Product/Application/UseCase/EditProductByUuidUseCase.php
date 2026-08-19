<?php

namespace Src\Product\Application\UseCase;

use Src\Product\Application\Contracts\Repositories\ProductRepositoryInterface;
use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\ValueObjects\NameProduct;
use Src\Product\Domain\ValueObjects\PriceProduct;
use Src\Product\Domain\ValueObjects\Sku;
use Src\Product\Domain\ValueObjects\Slug;
use Src\Product\Domain\ValueObjects\Uuid;

class EditProductByUuidUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Método excute.
     */
    public function excute(string $_uuid, string $_name, float $_price, string $_sku, string $_slug): ?Product
    {
        $uuid = Uuid::make($_uuid);
        $name = NameProduct::make($_name);
        $price = PriceProduct::make($_price);
        $sku = Sku::create($_sku);
        $slug = Slug::make($_slug);

        $product = $this->productRepository->ConsultProductByUuid($uuid);

        if (! $product) {
            throw new \Exception('Product not found', 404);
        }

        $product->setName($name);
        $product->setPrice($price);
        $product->setSku($sku);
        $product->setSlug($slug);

        return $this->productRepository->edit($product);
    }
}
