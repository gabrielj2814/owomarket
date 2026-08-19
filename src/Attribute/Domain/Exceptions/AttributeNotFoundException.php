<?php

declare(strict_types=1);

namespace Src\Attribute\Domain\Exceptions;

use Exception;

final class AttributeNotFoundException extends Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct(sprintf('No se encontró el atributo con el identificador "%s".', $identifier), 404);
    }
}
