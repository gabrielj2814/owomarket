<?php

declare(strict_types=1);

namespace Src\Brand\Application\UseCase;

use InvalidArgumentException;
use Src\Brand\Application\Contracts\BrandRepositoryInterface;
use Src\Brand\Domain\Entities\Brand;
use Src\Brand\Domain\ValueObjects\BrandDescription;
use Src\Brand\Domain\ValueObjects\BrandLogo;
use Src\Brand\Domain\ValueObjects\BrandName;
use Src\Brand\Domain\ValueObjects\BrandSlug;
use Src\Brand\Domain\ValueObjects\BrandStatus;

final class CreateBrandUseCase
{
    public function __construct(
        private readonly BrandRepositoryInterface $repository
    ) {}

    public function execute(
        string $name,
        ?string $slug = null,
        ?string $description = null,
        ?string $logo = null,
        bool $isActive = true,
        int $position = 0
    ): Brand {
        $brandName = BrandName::make($name);
        $brandSlug = BrandSlug::fromString($slug && trim($slug) !== '' ? $slug : $name);

        $existing = $this->repository->findBySlug($brandSlug);
        if ($existing !== null) {
            throw new InvalidArgumentException(
                sprintf('Ya existe una marca con el slug "%s".', $brandSlug->value())
            );
        }

        $brand = Brand::create(
            name: $brandName,
            slug: $brandSlug,
            description: BrandDescription::fromNullableString($description),
            logo: BrandLogo::fromNullableString($logo),
            isActive: new BrandStatus($isActive),
            position: $position
        );

        return $this->repository->save($brand);
    }
}
