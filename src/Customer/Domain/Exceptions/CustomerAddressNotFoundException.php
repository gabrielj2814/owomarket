<?php

declare(strict_types=1);

namespace Src\Customer\Domain\Exceptions;

use DomainException;
use Src\Customer\Domain\ValueObjects\AddressId;

final class CustomerAddressNotFoundException extends DomainException
{
    public static function withId(AddressId|string $id): self
    {
        $val = $id instanceof AddressId ? $id->value() : $id;

        return new self("No se encontró la dirección con ID '{$val}'.");
    }
}
