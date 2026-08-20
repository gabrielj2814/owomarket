<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\AddMessageToTicketUseCase;

final class AddSupportTicketMessagePOSTController
{
    public function __construct(
        private readonly AddMessageToTicketUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'user_id' => 'nullable|string',
            'sender_type' => 'nullable|in:tenant_owner,customer,support_agent,admin',
            'sender_name' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200',
        ]);

        $userId = (string) ($request->input('user_id') 
            ?: auth('central_customer')->id() 
            ?: auth()->id());

        $senderType = (string) ($request->input('sender_type') 
            ?: (auth('central_customer')->check() ? 'customer' : 'tenant_owner'));

        try {
            $files = $request->file('files');
            if ($files && ! is_array($files)) {
                $files = [$files];
            }

            $message = $this->useCase->execute([
                'ticket_id' => $id,
                'sender_type' => $senderType,
                'sender_id' => $userId,
                'sender_name' => (string) ($request->input('sender_name') ?: (auth()->user()?->name ?? 'Usuario')),
                'message' => (string) $request->input('message'),
                'files' => $files ?? [],
            ]);

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
