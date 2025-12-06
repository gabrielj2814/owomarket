<?php


namespace Src\User\Domain\Shared\Security;
interface PasswordValidator
{
    public function validate(string $password): void;
}


?>
