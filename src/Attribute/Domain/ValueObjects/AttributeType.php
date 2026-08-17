<?php

declare(strict_types=1);

namespace Src\Attribute\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class AttributeType extends StringValueObject
{
    public const TYPE_SELECT = 'select';

    public const TYPE_COLOR = 'color';

    public const TYPE_BUTTON = 'button';

    public const TYPE_RADIO = 'radio';

    public const ALLOWED_TYPES = [
        self::TYPE_SELECT,
        self::TYPE_COLOR,
        self::TYPE_BUTTON,
        self::TYPE_RADIO,
    ];

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function select(): self
    {
        return new self(self::TYPE_SELECT);
    }

    public static function color(): self
    {
        return new self(self::TYPE_COLOR);
    }

    public static function button(): self
    {
        return new self(self::TYPE_BUTTON);
    }

    public static function radio(): self
    {
        return new self(self::TYPE_RADIO);
    }

    protected function validate(string $value): void
    {
        $normalized = mb_strtolower(trim($value));

        if (! in_array($normalized, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Tipo de atributo no válido: "%s". Tipos permitidos: %s', $value, implode(', ', self::ALLOWED_TYPES))
            );
        }
    }
}
