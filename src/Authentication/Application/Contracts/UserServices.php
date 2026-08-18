<?php

namespace Src\Authentication\Application\Contracts;

interface UserServices
{
    /**
     * Método consultUserByEmail.
     */
    public function consultUserByEmail(string $email, string $host = ''): array;
}
