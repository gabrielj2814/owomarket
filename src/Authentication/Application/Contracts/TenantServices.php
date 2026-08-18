<?php

namespace Src\Authentication\Application\Contracts;

interface TenantServices
{
    /**
     * Método consultTenantLoginIsActive.
     */
    public function consultTenantLoginIsActive(string $slug, string $domain): array;
}
