<?php


namespace Src\User\Domain\Shared\Security;


interface PasswordHasher
{
    public function hash(string $plainPassword): string;
    public function verify(string $plainPassword, string $hash): bool;
    public function needsRehash(string $hash): bool;
}


?>
