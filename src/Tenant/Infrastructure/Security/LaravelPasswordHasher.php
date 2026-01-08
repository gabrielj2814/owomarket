<?php

namespace Src\Tenant\Infrastructure\Security;

use Illuminate\Hashing\HashManager;
use Src\Tenant\Domain\Shared\Security\PasswordHasher;

class LaravelPasswordHasher implements PasswordHasher
{
    public function __construct(
        private HashManager $hashManager
    ) {}

    public function hash(string $plainPassword): string
    {
        return $this->hashManager->make($plainPassword);
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        return $this->hashManager->check($plainPassword, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return $this->hashManager->needsRehash($hash);
    }
}


?>
