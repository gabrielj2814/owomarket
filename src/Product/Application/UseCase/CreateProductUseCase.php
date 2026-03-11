<?php


namespace Src\Product\Application\UseCase;

use Src\Product\Application\Contracts\Repositories\ProductRepositoryInterface;
use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\ValueObjects\NameProduct;
use Src\Product\Domain\ValueObjects\PriceProduct;
use Src\Product\Domain\ValueObjects\Sku;
use Src\Product\Domain\ValueObjects\Slug;
use Src\Product\Domain\ValueObjects\Uuid;

class CreateProductUseCase {

    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ){}


    public function execute(
        string $name,
        string $slug,
        float $price,
        string $sku
    ): Product
    {
        $nameProduct = NameProduct::make($name);
        $slugProduct = Slug::fromString($slug);
        $priceProduct = PriceProduct::make($price);
        $skuProduct = Sku::create($sku);


        $product = Product::create(
            name: $nameProduct,
            slug: $slugProduct,
            price: $priceProduct,
            sku: $skuProduct
        );

        return $this->productRepository->create($product);
    }


}


?>
