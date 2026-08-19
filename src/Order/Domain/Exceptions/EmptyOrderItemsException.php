<?php

declare(strict_types=1);

namespace Src\Order\Domain\Exceptions;

use Exception;

final class EmptyOrderItemsException extends Exception
{
    public function __construct()
    {
        parent::__construct('Una orden debe contener al menos un ítem o producto.');
    }
}
