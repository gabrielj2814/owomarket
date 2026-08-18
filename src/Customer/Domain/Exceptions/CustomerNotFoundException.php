<?php

declare(strict_types=1);

namespace Src\Customer\Domain\Exceptions;

use DomainException;
use Src\Customer\Domain\ValueObjects\CustomerId;

final class CustomerNotFoundException extends DomainException
{
    public static function withId(CustomerId|string $id): self
    {
        $val = $id instanceof CustomerId ? $id->value() : $id;

        return new self("No se encontró el cliente con ID '{$val}'.");
    }
}
