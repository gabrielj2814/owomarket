<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Notification\Application\UseCase\MarkNotificationReadUseCase;
use Src\Shared\Helper\ApiResponse;

/**
 * Marcar como leído: uno, o todos.
 *
 * Sin `id` marca el buzón entero. Es lo que espera cualquiera que abre la campana y cierra: si
 * marcar todo exigiera ir uno por uno, nadie lo haría y el contador se quedaría clavado hasta
 * dejar de significar nada.
 */
final class MarkNotificationsReadPOSTController
{
    public function __construct(
        private readonly MarkNotificationReadUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $destinatario = $request->user();

        if ($destinatario === null) {
            return ApiResponse::error('Inicia sesión para marcar tus notificaciones.', 401);
        }

        $validado = $request->validate([
            'id' => ['nullable', 'string'],
        ]);

        $id = $validado['id'] ?? null;

        if ($id === null) {
            return ApiResponse::success(
                data: ['marked' => $this->useCase->all($destinatario)],
                message: 'Notificaciones marcadas como leídas.'
            );
        }

        /*
         * 404 y no 403 cuando el aviso no es suyo. El caso de uso filtra por destinatario, así
         * que aquí no se distingue «no existe» de «es de otro» --y distinguirlo le confirmaría
         * a quien prueba identificadores ajenos cuáles existen--.
         */
        if (! $this->useCase->execute($destinatario, $id)) {
            return ApiResponse::error('Esa notificación no existe en tu buzón.', 404);
        }

        return ApiResponse::success(
            data: ['marked' => 1],
            message: 'Notificación marcada como leída.'
        );
    }
}
