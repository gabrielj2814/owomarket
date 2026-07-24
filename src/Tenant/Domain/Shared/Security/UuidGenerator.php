<?php

namespace Src\Tenant\Domain\Shared\Security;

interface UuidGenerator
{
    public function generate(): string;
    public function isValid(string $uuid): bool;
}
