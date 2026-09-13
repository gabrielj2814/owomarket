<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Notification\Application\Service\EmailDelivery;
use Src\Shared\Helper\ApiResponse;

/**
 * Activar o desactivar los correos opcionales.
 *
 * **Solo gobierna lo opcional.** Los tres avisos críticos —reclamación abierta, entrega
 * declarada y KYC resuelto— salen por correo igualmente, porque tienen un reloj o dinero detrás
 * y apagarlos sería dejar apagar la alarma de incendios. La pantalla lo dice, para que nadie
 * crea que apagó algo que no apagó.
 *
 * La preferencia es de quien pregunta: sale del guard, nunca del cuerpo. Aceptar un
 * identificador aquí dejaría a cualquiera activarle el correo a otro.
 */
final class UpdateNotificationEmailPreferencePOSTController
{
    public function __construct(
        private readonly EmailDelivery $correo
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $destinatario = $request->user();

        if ($destinatario === null) {
            return ApiResponse::error('Inicia sesión para cambiar tus notificaciones.', 401);
        }

        $validado = $request->validate([
            'email_enabled' => ['required', 'boolean'],
        ]);

        $this->correo->setEmailEnabled($destinatario, (bool) $validado['email_enabled']);

        return ApiResponse::success(
            data: ['email_enabled' => (bool) $validado['email_enabled']],
            message: $validado['email_enabled']
                ? 'Te enviaremos también un correo con tus notificaciones.'
                : 'Solo verás las notificaciones en la campana. Los avisos urgentes seguirán llegándote por correo.'
        );
    }
}
