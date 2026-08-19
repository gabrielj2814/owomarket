<?php

declare(strict_types=1);

namespace Src\Brand\Domain\Entities;

use Src\Brand\Domain\ValueObjects\BrandDescription;
use Src\Brand\Domain\ValueObjects\BrandId;
use Src\Brand\Domain\ValueObjects\BrandLogo;
use Src\Brand\Domain\ValueObjects\BrandName;
use Src\Brand\Domain\ValueObjects\BrandSlug;
use Src\Brand\Domain\ValueObjects\BrandStatus;

final class Brand
{
    public function __construct(
        private ?BrandId $id,
        private BrandName $name,
        private BrandSlug $slug,
        private BrandDescription $description,
        private BrandLogo $logo,
        private BrandStatus $isActive,
        private int $position = 0,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    public static function create(
        BrandName $name,
        BrandSlug $slug,
        ?BrandDescription $description = null,
        ?BrandLogo $logo = null,
        ?BrandStatus $isActive = null,
        int $position = 0
    ): self {
        return new self(
            id: null,
            name: $name,
            slug: $slug,
            description: $description ?? BrandDescription::fromNullableString(null),
            logo: $logo ?? BrandLogo::fromNullableString(null),
            isActive: $isActive ?? BrandStatus::active(),
            position: $position
        );
    }

    public function id(): ?BrandId
    {
        return $this->id;
    }

    public function name(): BrandName
    {
        return $this->name;
    }

    public function slug(): BrandSlug
    {
        return $this->slug;
    }

    public function description(): BrandDescription
    {
        return $this->description;
    }

    public function logo(): BrandLogo
    {
        return $this->logo;
    }

    public function isActive(): BrandStatus
    {
        return $this->isActive;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function changeName(BrandName $name): void
    {
        $this->name = $name;
    }

    public function changeSlug(BrandSlug $slug): void
    {
        $this->slug = $slug;
    }

    public function changeDescription(BrandDescription $description): void
    {
        $this->description = $description;
    }

    public function changeLogo(BrandLogo $logo): void
    {
        $this->logo = $logo;
    }

    public function activate(): void
    {
        $this->isActive = BrandStatus::active();
    }

    public function deactivate(): void
    {
        $this->isActive = BrandStatus::inactive();
    }

    public function changePosition(int $position): void
    {
        $this->position = $position;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id?->value(),
            'name' => $this->name->value(),
            'slug' => $this->slug->value(),
            'description' => $this->description->value(),
            'logo' => $this->logo->value(),
            'is_active' => $this->isActive->value(),
            'position' => $this->position,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
