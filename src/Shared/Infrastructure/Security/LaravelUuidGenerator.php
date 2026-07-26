<?php

namespace Src\Shared\Infrastructure\Security;

use Illuminate\Support\Str;
use Src\Shared\Domain\Contracts\UuidGenerator;

class LaravelUuidGenerator implements UuidGenerator
{
    public function generate(): string
    {
        return Str::uuid()->toString();
    }

    public function isValid(string $uuid): bool
    {
        return Str::isUuid($uuid);
    }
}
