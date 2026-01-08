<?php


namespace Src\Tenant\Domain\Shared\Security;

interface PasswordValidator
{
    public function validate(string $password): void;
}


?>
