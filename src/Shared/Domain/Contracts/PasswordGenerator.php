<?php

namespace Src\Shared\Domain\Contracts;

interface PasswordGenerator
{
    public function generate(int $length = 12): string;
}
