<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\AddMessageToTicketUseCase;
use Src\SupportTicket\Infrastructure\Http\Support\ResolvesSupportRequester;

final class AddSupportTicketMessagePOSTController
{
    use ResolvesSupportRequester;

    public function __construct(
        private readonly AddMessageToTicketUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200',
        ]);

        // La identidad y el tipo de remitente SIEMPRE salen de la sesión.
        // Antes se aceptaban 'user_id' y 'sender_type' del body, incluyendo
        // 'admin'/'support_agent': cualquiera podía insertar un mensaje que
        // la víctima veía como oficial de OwoMarket (hallazgo A6). El envío
        // como staff real sigue disponible en AdminReplySupportTicketPOSTController,
        // protegido por ['auth','staff:manage_support'].
        $requester = $this->resolveSupportRequester($request);

        if ($requester === null) {
            return ApiResponse::error('Debes iniciar sesión para responder en soporte.', 401);
        }

        try {
            $files = $request->file('files');
            if ($files && ! is_array($files)) {
                $files = [$files];
            }

            $message = $this->useCase->execute([
                'ticket_id' => $id,
                'sender_type' => $requester['type'],
                'sender_id' => $requester['id'],
                'sender_name' => $requester['name'],
                'message' => (string) $request->input('message'),
                'files' => $files ?? [],
            ], requesterId: $requester['id']);

            return ApiResponse::success(
                data: $message,
                message: 'Mensaje agregado exitosamente',
                code: 201
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
