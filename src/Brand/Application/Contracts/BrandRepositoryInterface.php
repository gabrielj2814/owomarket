<?php

declare(strict_types=1);

namespace Src\Brand\Application\Contracts;

use Src\Brand\Application\DTOs\BrandFilterCriteria;
use Src\Brand\Application\DTOs\PaginatedBrandsResult;
use Src\Brand\Domain\Entities\Brand;
use Src\Brand\Domain\ValueObjects\BrandId;
use Src\Brand\Domain\ValueObjects\BrandSlug;

interface BrandRepositoryInterface
{
    public function save(Brand $brand): Brand;

    public function findById(BrandId $id): ?Brand;

    public function findBySlug(BrandSlug $slug): ?Brand;

    public function update(Brand $brand): Brand;

    public function delete(BrandId $id): void;

    public function filter(BrandFilterCriteria $criteria): PaginatedBrandsResult;

    /**
     * @return Brand[]
     */
    public function listAllActive(): array;
}
