<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\CreateSupportTicketUseCase;
use Src\SupportTicket\Infrastructure\Http\Support\ResolvesSupportRequester;

final class CreateSupportTicketPOSTController
{
    use ResolvesSupportRequester;

    public function __construct(
        private readonly CreateSupportTicketUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'tenant_id' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200', // max 50MB per file
        ]);

        // La identidad y el tipo de solicitante SIEMPRE salen de la sesión.
        // Antes se aceptaban 'user_id' y 'requester_type' del body — incluso
        // 'staff', que no tiene ninguna verificación detrás (hallazgo A6).
        $requester = $this->resolveSupportRequester($request);

        if ($requester === null) {
            return ApiResponse::error('Debes iniciar sesión para crear un ticket de soporte.', 401);
        }

        try {
            $files = $request->file('files');
            if ($files && ! is_array($files)) {
                $files = [$files];
            }

            $ticket = $this->useCase->execute([
                'user_id' => $requester['id'],
                'requester_type' => $requester['type'],
                'sender_name' => $requester['name'],
                'tenant_id' => $request->input('tenant_id'),
                'category' => (string) $request->input('category', 'technical_error'),
                'priority' => (string) $request->input('priority', 'medium'),
                'subject' => (string) $request->input('subject'),
                'description' => (string) $request->input('description'),
                'metadata' => [
                    'user_agent' => $request->userAgent(),
                    'ip' => $request->ip(),
                ],
                'files' => $files ?? [],
            ]);

            return ApiResponse::success(
                data: $ticket,
                message: 'Ticket de soporte creado exitosamente.',
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
