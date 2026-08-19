<?php

declare(strict_types=1);

namespace Src\TenantSettings\Domain\Exceptions;

use DomainException;

final class InvalidSettingKeyException extends DomainException
{
    public static function forValue(string $key): self
    {
        return new self("La clave de configuración '{$key}' es inválida. Debe contener sólo caracteres alfanuméricos en minúsculas, guiones bajos o puntos.");
    }
}
