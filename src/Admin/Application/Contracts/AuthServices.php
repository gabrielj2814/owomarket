<?php

namespace Src\Admin\Application\Contracts;

use Src\Admin\Domain\ValueObjects\Uuid;

interface AuthServices
{
    /**
     * Método consultAuthUserByUuid.
     */
    public function consultAuthUserByUuid(Uuid $uuid): array;
}
