<?php

namespace Src\Product\Application\UseCase;

use Exception;
use Src\Product\Application\Contracts\Repositories\ProductRepositoryInterface;
use Src\Product\Domain\ValueObjects\Uuid;

class DeleteProductByUuidUseCase {

    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ){}

    public function excute(string $_uuid): void {
        $uuid= Uuid::make($_uuid);

        $product = $this->productRepository->ConsultProductByUuid($uuid);

        if(!$product){
            throw new Exception('Product not found', 404);
        }

        $this->productRepository->delete($uuid);
    }



}



?>
