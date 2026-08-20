<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\CreateSupportTicketUseCase;

final class CreateTenantStoreSupportTicketPOSTController
{
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
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200',
        ]);

        $userId = (string) (auth()->id() ?: $request->input('user_id'));
        $tenantId = function_exists('tenant') && tenant() ? (string) tenant('id') : $request->input('tenant_id');

        if (empty($userId)) {
            return ApiResponse::error('Usuario no autenticado', 401);
        }

        try {
            $files = $request->file('files');
            if ($files && ! is_array($files)) {
                $files = [$files];
            }

            $ticket = $this->useCase->execute([
                'user_id' => $userId,
                'requester_type' => 'tenant_owner',
                'sender_name' => auth()->user()?->name ?? 'Administrador de Tienda',
                'tenant_id' => $tenantId,
                'category' => (string) $request->input('category', 'technical_error'),
                'priority' => (string) $request->input('priority', 'medium'),
                'subject' => (string) $request->input('subject'),
                'description' => (string) $request->input('description'),
                'metadata' => [
                    'tenant_id' => $tenantId,
                    'tenant_domain' => request()->getHost(),
                    'user_agent' => $request->userAgent(),
                ],
                'files' => $files ?? [],
            ]);

            return ApiResponse::success(
                data: $ticket,
                message: 'Ticket de soporte creado exitosamente para esta tienda.',
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
