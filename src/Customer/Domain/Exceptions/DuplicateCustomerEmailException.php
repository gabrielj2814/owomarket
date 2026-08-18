<?php

declare(strict_types=1);

namespace Src\Customer\Domain\Exceptions;

use DomainException;
use Src\Customer\Domain\ValueObjects\CustomerEmail;

final class DuplicateCustomerEmailException extends DomainException
{
    public static function withEmail(CustomerEmail|string $email): self
    {
        $val = $email instanceof CustomerEmail ? $email->value() : $email;

        return new self("Ya existe un cliente registrado con el correo '{$val}'.");
    }
}
