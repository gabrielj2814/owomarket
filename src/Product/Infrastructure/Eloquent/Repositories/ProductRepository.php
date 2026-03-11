<?php

namespace Src\Product\Infrastructure\Eloquent\Repositories;

use Illuminate\Support\Facades\Log;
use Src\Product\Application\Contracts\Repositories\ProductRepositoryInterface;
use Src\Product\Domain\Entities\Product;
use Src\Product\Infrastructure\Eloquent\Models\Product as ModelsProduct;

class ProductRepository implements ProductRepositoryInterface {


    public function create(Product $product): Product
    {

        // Log::info('Product details: ID=' . $product->getId()->value() . ', Slug=' . $product->getSlug()->value() . ', Price=' . $product->getPrice()->value() . ', Name=' . $product->getName()->value() . ', Sku=' . $product->getSku()->value());
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

}


?>
