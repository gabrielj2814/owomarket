<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Notification\Application\UseCase\ListNotificationsUseCase;
use Src\Shared\Helper\ApiResponse;

/**
 * El buzón de quien pregunta, sea del personal o un comprador.
 *
 * **Un solo controlador para las dos audiencias.** Lo único que cambia es el guard que lo
 * protege, y `$request->user()` ya devuelve el destinatario correcto: el middleware `auth`
 * declara qué guard manda en esta petición, así que bajo `auth:central_customer` sale un
 * `CentralCustomer` y bajo `auth` sale un `User`.
 *
 * Los dos son exactamente las clases del mapa de morfismos, y eso no es casualidad: `config/auth.php`
 * ya apuntaba a ellas. Duplicar este controlador por audiencia sería dos caminos al mismo sitio
 * que se separan en cuanto alguien toque uno.
 */
final class ListNotificationsGETController
{
    public function __construct(
        private readonly ListNotificationsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $destinatario = $request->user();

        if ($destinatario === null) {
            return ApiResponse::error('Inicia sesión para ver tus notificaciones.', 401);
        }

        return ApiResponse::success(
            data: $this->useCase->execute($destinatario),
            message: 'Notificaciones recuperadas.'
        );
    }
}
