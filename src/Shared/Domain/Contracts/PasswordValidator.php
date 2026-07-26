<?php

namespace Src\Shared\Domain\Contracts;

interface PasswordValidator
{
    public function validate(string $plainPassword): void;
}
