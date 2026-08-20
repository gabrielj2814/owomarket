<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\CreateSupportTicketUseCase;

final class CreateSupportTicketPOSTController
{
    public function __construct(
        private readonly CreateSupportTicketUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|string',
            'requester_type' => 'nullable|in:tenant_owner,customer,guest,staff',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'tenant_id' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200', // max 50MB per file
        ]);

        $userId = (string) ($request->input('user_id') 
            ?: auth('central_customer')->id() 
            ?: auth()->id());

        if (empty($userId)) {
            return ApiResponse::error('Usuario no autenticado o user_id no provisto.', 401);
        }

        $requesterType = (string) ($request->input('requester_type') 
            ?: (auth('central_customer')->check() ? 'customer' : 'tenant_owner'));

        try {
            $files = $request->file('files');
            if ($files && ! is_array($files)) {
                $files = [$files];
            }

            $ticket = $this->useCase->execute([
                'user_id' => $userId,
                'requester_type' => $requesterType,
                'sender_name' => auth()->user()?->name ?? 'Usuario',
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
