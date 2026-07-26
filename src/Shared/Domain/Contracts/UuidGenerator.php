<?php

namespace Src\Shared\Domain\Contracts;

interface UuidGenerator
{
    public function generate(): string;
    public function isValid(string $uuid): bool;
}
