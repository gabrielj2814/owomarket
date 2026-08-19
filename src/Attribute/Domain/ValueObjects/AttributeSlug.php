<?php

declare(strict_types=1);

namespace Src\Attribute\Domain\ValueObjects;

use Src\Attribute\Domain\Exceptions\InvalidAttributeSlugException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class AttributeSlug extends StringValueObject
{
    public static function fromString(string $value): self
    {
        $sanitized = self::sanitize($value);

        return new self($sanitized);
    }

    protected function validate(string $value): void
    {
        if (empty($value)) {
            throw new InvalidAttributeSlugException($value);
        }
    }

    public static function sanitize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('~[^\pL\d]+~u', '-', $text) ?? '';
        $text = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('~[^-\w]+~', '', $text) ?? '';
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text) ?? '';

        return $text;
    }
}
