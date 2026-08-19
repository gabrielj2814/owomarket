<?php

namespace Src\Tenant\Application\Contracts;

use Src\Shared\Domain\ValueObjects\Uuid;

interface AuthServices
{
    /**
     * Método consultAuthUserByUuid.
     */
    public function consultAuthUserByUuid(Uuid $uuid, string $baseUrl = ''): array;
}
