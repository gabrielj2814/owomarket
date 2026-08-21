<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\AddMessageToTicketUseCase;

final class AddTenantStoreSupportMessagePOSTController
{
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

        $userId = (string) (auth()->id() ?: $request->input('user_id'));

        if (empty($userId)) {
            return ApiResponse::error('Usuario no autenticado', 401);
        }

        try {
            $files = $request->file('files');
            if ($files && ! is_array($files)) {
                $files = [$files];
            }

            $message = $this->useCase->execute([
                'ticket_id' => $id,
                'sender_type' => 'tenant_owner',
                'sender_id' => $userId,
                'sender_name' => auth()->user()?->name ?? 'Administrador de Tienda',
                'message' => (string) $request->input('message'),
                'files' => $files ?? [],
            ], requesterId: $userId);

            return ApiResponse::success(
                data: $message,
                message: 'Respuesta enviada exitosamente',
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
