<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Alias de compatibilidad hacia el modelo DDD canónico en el Bounded Context.
 */
class Customer extends \Src\Customer\Infrastructure\Eloquent\Models\Customer
{
}
