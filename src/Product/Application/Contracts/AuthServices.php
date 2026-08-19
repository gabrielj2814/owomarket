<?php

namespace Src\Product\Application\Contracts;

use Src\Shared\Domain\ValueObjects\Uuid;

interface AuthServices
{
    /**
     * Método consultAuthUserByUuid.
     */
    public function consultAuthUserByUuid(Uuid $uuid, string $baseUrl = ''): array;
}
