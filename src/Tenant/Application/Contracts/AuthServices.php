<?php

namespace Src\Tenant\Application\Contracts;

use Src\Tenant\Domain\ValueObjects\Uuid;

interface AuthServices
{
    /**
     * Método consultAuthUserByUuid.
     */
    public function consultAuthUserByUuid(Uuid $uuid, string $baseUrl = ''): array;
}
