<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Src\Product\Application\Contracts\ProductRepositoryInterface;
use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\Exceptions\ProductNotFoundException;
use Src\Product\Domain\ValueObjects\ProductSlug;

final class ConsultProductBySlugUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function execute(string $slug): Product
    {
        $productSlug = ProductSlug::fromString($slug);
        $product = $this->repository->findBySlug($productSlug);

        if ($product === null) {
            throw new ProductNotFoundException($slug);
        }

        return $product;
    }
}
