<?php

namespace Src\Tenant\Infrastructure\Security;

use Illuminate\Support\Str;
use Src\Tenant\Domain\Shared\Security\UuidGenerator;

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
