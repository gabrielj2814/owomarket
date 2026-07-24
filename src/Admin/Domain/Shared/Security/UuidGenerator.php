<?php

namespace Src\Admin\Domain\Shared\Security;

interface UuidGenerator
{
    public function generate(): string;
    public function isValid(string $uuid): bool;
}
