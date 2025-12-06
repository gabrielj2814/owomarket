<?php


namespace Src\Authentication\Domain\Shared\Security;

interface PasswordValidator
{
    public function validate(string $password): void;
}


?>
