<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\CentralCustomer\Application\Service\ClaimResponseWindow;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Notification\Application\Contracts\NotificationDispatcher;

/**
 * El segundo aviso: a esta reclamación le queda un día.
 *
 * **Es lo que convierte el reloj en justo.** `AutoResolveStaleReturnsUseCase` resuelve a favor
 * del comprador lo que la tienda no contesta, y esa consecuencia solo es defendible si el
 * comerciante tuvo ocasión de enterarse. Perder una venta tras un aviso al abrirse y otro antes
 * de vencer es una decisión suya; perderla con un solo aviso de hace cinco días es un descuido
 * que la plataforma podía haber evitado.
 *
 * ## El plazo sale del mismo servicio que el reloj
 *
 * `ClaimResponseWindow` es de donde `AutoResolveStaleReturnsUseCase` saca su corte. Calcular
 * aquí los días de otra forma sería avisar de un vencimiento que no coincide con el real: el
 * recordatorio llegaría tarde, o llegaría para reclamaciones que aún tienen días.
 *
 * ## No se repite, y de eso se encarga el despachador
 *
 * Este caso de uso propone; el freno decide. Se llama todos los días y el estado no cambia hasta
 * que alguien conteste, así que el recordatorio saldría cada madrugada — y un aviso repetido no
 * avisa el doble: avisa menos.
 */
final class RemindExpiringClaimsUseCase
{
    /**
     * Cuántos días antes de vencer se recuerda.
     *
     * Uno. Menos no da margen para responder; más lo convierte en ruido, porque el plazo entero
     * son cinco días y avisar el segundo es avisar de nada.
     *
     * Se comprueba con `<=` y no con `==` a propósito: si el comando no corre un día —la máquina
     * apagada, un despliegue— una reclamación pasaría de 2 a 0 sin recordatorio ninguno.
     */
    private const DIAS_DE_AVISO = 1;

    public function __construct(
        private readonly ClaimResponseWindow $plazo,
        private readonly NotificationDispatcher $avisos
    ) {}

    /**
     * @return int Reclamaciones por las que se avisó.
     */
    public function execute(): int
    {
        $abiertas = CustomerReturnRequest::whereIn('status', CustomerReturnRequest::ABIERTAS)->get();

        $avisadas = 0;

        foreach ($abiertas as $reclamacion) {
            if ($this->plazo->daysLeftFrom($reclamacion->created_at) > self::DIAS_DE_AVISO) {
                continue;
            }

            $this->avisos->claimAboutToExpire($reclamacion->id);
            $avisadas++;
        }

        return $avisadas;
    }
}
