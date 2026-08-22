<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Domain\Contracts;

use Src\ExchangeRate\Domain\Entities\ExchangeRate;

/**
 * Avisa a alguien de que la tasa oficial lleva días sin actualizarse (hallazgo N20).
 *
 * El hallazgo D4 escaló el fallback prolongado de `warning` a `error`, pero un `error`
 * en el log no despierta a nadie: el sitio podía seguir facturando semanas con una tasa
 * congelada mientras la línea se acumulaba en `storage/logs`.
 *
 * El contrato vive en el dominio y la elección del canal en infraestructura, para que
 * cambiar el correo por un webhook no toque el caso de uso.
 */
interface StaleRateAlerter
{
    /**
     * @param  ExchangeRate  $activeRate  La tasa congelada con la que se sigue facturando.
     * @param  int  $daysStale  Días transcurridos desde su fecha valor.
     * @param  string  $errorMessage  Motivo por el que falló la sincronización.
     */
    public function alertStaleRate(ExchangeRate $activeRate, int $daysStale, string $errorMessage): void;
}
