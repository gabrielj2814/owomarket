<?php

namespace Src\Product\Infrastructure\Eloquent\Repositories;

use Illuminate\Support\Facades\Log;
use Src\Product\Application\Contracts\Repositories\ProductRepositoryInterface;
use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\ValueObjects\NameProduct;
use Src\Product\Domain\ValueObjects\PriceProduct;
use Src\Product\Domain\ValueObjects\Sku;
use Src\Product\Domain\ValueObjects\Slug;
use Src\Product\Domain\ValueObjects\Uuid;
use Src\Product\Infrastructure\Eloquent\Models\Product as ModelsProduct;

class ProductRepository implements ProductRepositoryInterface {


    public function create(Product $product): Product
    {
        $productModel = new ModelsProduct();
        $productModel->id = $product->getId()->value();
        $productModel->name = $product->getName()->value();
        $productModel->slug = $product->getSlug()->value();
        $productModel->price = $product->getPrice()->value();
        $productModel->sku = $product->getSku()->value();
        // Log::info('Product details: ID=' . $productModel->id . ', Slug=' . $productModel->slug . ', Price=' . $productModel->price. ', Name=' . $productModel->name.', Sku=' . $productModel->sku);
        // $sql = $productModel->getQuery()->toRawSql();
        // Log::info('Generated SQL: ' . $sql);
        $productModel->save();

        return $product;
    }

    public function ConsultProductByUuid(Uuid $id): ?Product
    {
        $productModel = ModelsProduct::where('id', $id->value())->first();

        if (!$productModel) {
            return null;
        }

        return Product::reconstitute(
            id: Uuid::make($productModel->id),
            name: NameProduct::make($productModel->name),
            slug: Slug::make($productModel->slug),
            price: PriceProduct::make($productModel->price),
            sku: Sku::create($productModel->sku)
        );
    }

    public function delete(Uuid $id): void
    {
        $productModel = ModelsProduct::where('id', $id->value())->first();

        if ($productModel) {
            $productModel->delete();
        }
    }

}


?>
