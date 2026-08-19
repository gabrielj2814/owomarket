<?php

declare(strict_types=1);

namespace Src\TenantSettings\Domain\Exceptions;

use DomainException;

final class SettingNotFoundException extends DomainException
{
    public static function forKey(string $key): self
    {
        return new self("No se encontró la configuración con clave '{$key}'.");
    }

    public static function forId(string $id): self
    {
        return new self("No se encontró la configuración con identificador '{$id}'.");
    }
}
