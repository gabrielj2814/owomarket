<?php

namespace Src\Shared\Domain\Contracts;

interface PasswordHasher
{
    public function hash(string $plainPassword): string;
    public function verify(string $plainPassword, string $hash): bool;
    public function needsRehash(string $hash): bool;
}
