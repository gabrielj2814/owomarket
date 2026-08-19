<?php

namespace Src\Product\Application\UseCase;

use Src\Product\Application\Contracts\Repositories\ProductRepositoryInterface;
use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\ValueObjects\Uuid;

class ConsultProductByUuidUseCase
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
    public function excute(string $_uuid): ?Product
    {
        $uuid = Uuid::make($_uuid);

        return $this->productRepository->ConsultProductByUuid($uuid);
    }
}
