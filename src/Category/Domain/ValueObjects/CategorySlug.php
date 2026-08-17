<?php

declare(strict_types=1);

namespace Src\Category\Domain\ValueObjects;

use Src\Category\Domain\Exceptions\InvalidCategorySlugException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class CategorySlug extends StringValueObject
{
    public static function make(string $value): self
    {
        return new self($value);
    }

    public static function fromString(string $value): self
    {
        $normalized = self::sanitize($value);

        return new self($normalized);
    }

    protected function validate(string $value): void
    {
        if (empty($value) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new InvalidCategorySlugException($value);
        }
    }

    public static function sanitize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $text) ?? '';
        // Trim hyphens from both ends
        $text = trim($text, '-');

        // Transliterate accented characters if needed
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($clean !== false) {
            $clean = preg_replace('/[^a-z0-9-]+/', '', $clean) ?? '';
            $clean = preg_replace('/-+/', '-', $clean) ?? '';
            $text = trim($clean, '-');
        }

        return $text ?: 'category';
    }
}
