<?php

namespace Src\Authentication\Domain\Shared\Security;

interface UuidGenerator
{
    public function generate(): string;
    public function isValid(string $uuid): bool;
}
