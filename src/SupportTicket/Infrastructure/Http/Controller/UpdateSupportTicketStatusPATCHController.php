<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\UpdateTicketStatusUseCase;
use Src\SupportTicket\Infrastructure\Http\Support\ResolvesSupportRequester;

final class UpdateSupportTicketStatusPATCHController
{
    use ResolvesSupportRequester;

    public function __construct(
        private readonly UpdateTicketStatusUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,waiting_reply,resolved,closed',
        ]);

        // Antes esta ruta no exigía ni sesión ni propiedad del ticket:
        // cualquiera podía cerrar o reabrir el ticket de otra persona.
        $requester = $this->resolveSupportRequester($request);

        if ($requester === null) {
            return ApiResponse::error('Debes iniciar sesión para actualizar este ticket.', 401);
        }

        try {
            $ticket = $this->useCase->execute($id, (string) $request->input('status'), requesterId: $requester['id']);

            return ApiResponse::success(
                data: $ticket,
                message: 'Estado del ticket actualizado exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
