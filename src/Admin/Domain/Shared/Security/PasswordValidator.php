<?php


namespace Src\Admin\Domain\Shared\Security;

interface PasswordValidator
{
    public function validate(string $password): void;
}


?>
