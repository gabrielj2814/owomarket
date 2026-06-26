<?php


namespace Src\Product\Application\Contracts\Repositories;

use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\ValueObjects\NameProduct;
use Src\Product\Domain\ValueObjects\PriceProduct;
use Src\Product\Domain\ValueObjects\Slug;
use Src\Product\Domain\ValueObjects\Uuid;

interface ProductRepositoryInterface {

    /**
     * Método create.
     */

    public function create(Product $product): Product;

    /**
     * Método ConsultProductByUuid.
     */

    public function ConsultProductByUuid(Uuid $uuid): ?Product;

    /**
     * Método edit.
     */

    public function edit(Product $product): Product;

    /**
     * Método delete.
     */

    public function delete(Uuid $id): void;


}


?>
