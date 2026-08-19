<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Src\Product\Application\Contracts\ProductRepositoryInterface;
use Src\Product\Domain\Exceptions\ProductNotFoundException;
use Src\Product\Domain\ValueObjects\ProductId;

final class DeleteProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function execute(string $id): void
    {
        $productId = ProductId::fromString($id);
        $product = $this->repository->findById($productId);

        if ($product === null) {
            throw new ProductNotFoundException($id);
        }

        $this->repository->delete($productId);
    }
}
