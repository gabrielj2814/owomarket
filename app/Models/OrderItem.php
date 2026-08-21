<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Alias de compatibilidad hacia el modelo DDD canónico en el Bounded Context.
 */
class OrderItem extends \Src\Order\Infrastructure\Eloquent\Models\OrderItem
{
}
