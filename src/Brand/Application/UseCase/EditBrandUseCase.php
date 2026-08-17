<?php

declare(strict_types=1);

namespace Src\Brand\Application\UseCase;

use InvalidArgumentException;
use Src\Brand\Application\Contracts\BrandRepositoryInterface;
use Src\Brand\Domain\Entities\Brand;
use Src\Brand\Domain\Exceptions\BrandNotFoundException;
use Src\Brand\Domain\ValueObjects\BrandDescription;
use Src\Brand\Domain\ValueObjects\BrandId;
use Src\Brand\Domain\ValueObjects\BrandLogo;
use Src\Brand\Domain\ValueObjects\BrandName;
use Src\Brand\Domain\ValueObjects\BrandSlug;

final class EditBrandUseCase
{
    public function __construct(
        private readonly BrandRepositoryInterface $repository
    ) {}

    public function execute(
        int $id,
        string $name,
        ?string $slug = null,
        ?string $description = null,
        ?string $logo = null,
        bool $isActive = true,
        int $position = 0
    ): Brand {
        $brandId = new BrandId($id);
        $brand = $this->repository->findById($brandId);

        if ($brand === null) {
            throw new BrandNotFoundException($id);
        }

        $brandName = BrandName::make($name);
        $brandSlug = BrandSlug::fromString($slug && trim($slug) !== '' ? $slug : $name);

        $existingWithSlug = $this->repository->findBySlug($brandSlug);
        if ($existingWithSlug !== null && $existingWithSlug->id()?->value() !== $brandId->value()) {
            throw new InvalidArgumentException(
                sprintf('El slug "%s" ya está en uso por otra marca.', $brandSlug->value())
            );
        }

        $brand->changeName($brandName);
        $brand->changeSlug($brandSlug);
        $brand->changeDescription(BrandDescription::fromNullableString($description));
        $brand->changeLogo(BrandLogo::fromNullableString($logo));
        $isActive ? $brand->activate() : $brand->deactivate();
        $brand->changePosition($position);

        return $this->repository->update($brand);
    }
}
