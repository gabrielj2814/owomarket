<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Alias de compatibilidad hacia el modelo DDD canónico en el Bounded Context.
 */
class Payment extends \Src\Payment\Infrastructure\Eloquent\Models\Payment {}
