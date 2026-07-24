<?php

namespace Src\User\Domain\Shared\Security;

interface UuidGenerator
{
    public function generate(): string;
    public function isValid(string $uuid): bool;
}
