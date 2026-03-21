<?php


namespace Src\Product\Application\Contracts\Repositories;

use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\ValueObjects\NameProduct;
use Src\Product\Domain\ValueObjects\PriceProduct;
use Src\Product\Domain\ValueObjects\Slug;
use Src\Product\Domain\ValueObjects\Uuid;

interface ProductRepositoryInterface {

    public function create(Product $product): Product;

    public function ConsultProductByUuid(Uuid $uuid): ?Product;

    // public function edit(Product $product): Product;

    // public function delete(Uuid $id): void;


}


?>
