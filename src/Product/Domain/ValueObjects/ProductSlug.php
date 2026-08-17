<?php

declare(strict_types=1);

namespace Src\Product\Domain\ValueObjects;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class ProductSlug extends StringValueObject
{
    public static function fromString(string $value): self
    {
        $slug = Str::slug($value);

        if (empty($slug)) {
            throw new InvalidArgumentException('El slug del producto no puede estar vacío.');
        }

        return new self($slug);
    }

    protected function validate(string $value): void
    {
        if (empty(trim($value))) {
            throw new InvalidArgumentException('El slug del producto no puede estar vacío.');
        }
    }
}
