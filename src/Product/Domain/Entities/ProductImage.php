<?php

declare(strict_types=1);

namespace Src\Product\Domain\Entities;

final class ProductImage
{
    public function __construct(
        private ?string $id,
        private string $imagePath,
        private ?string $altText = null,
        private bool $isDefault = false,
        private int $order = 0
    ) {}

    public static function create(
        string $imagePath,
        ?string $altText = null,
        bool $isDefault = false,
        int $order = 0,
        ?string $id = null
    ): self {
        return new self(
            id: $id,
            imagePath: $imagePath,
            altText: $altText,
            isDefault: $isDefault,
            order: $order
        );
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function imagePath(): string
    {
        return $this->imagePath;
    }

    public function altText(): ?string
    {
        return $this->altText;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function order(): int
    {
        return $this->order;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'image_path' => $this->imagePath,
            'alt_text' => $this->altText,
            'is_default' => $this->isDefault,
            'order' => $this->order,
        ];
    }
}
