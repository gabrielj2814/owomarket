<?php


namespace Src\Product\Application\Contracts\Repositories;

use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\ValueObjects\NameProduct;
use Src\Product\Domain\ValueObjects\PriceProduct;
use Src\Product\Domain\ValueObjects\Slug;

interface ProductRepositoryInterface {

    public function create(Product $product): Product;


}


?>
