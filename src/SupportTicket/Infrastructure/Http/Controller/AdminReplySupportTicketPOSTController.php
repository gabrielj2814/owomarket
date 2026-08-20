<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\AddMessageToTicketUseCase;
use Src\SupportTicket\Application\UseCase\GetSupportTicketDetailUseCase;

final class AdminReplySupportTicketPOSTController
{
    public function __construct(
        private readonly AddMessageToTicketUseCase $addMessageUseCase,
        private readonly GetSupportTicketDetailUseCase $detailUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'status' => 'nullable|string|in:open,in_progress,waiting_reply,resolved,closed',
            'attachments.*' => 'nullable|file|max:51200',
        ]);

        try {
            $user = auth()->user();
            $adminUserId = (string) ($user?->id ?? 'system');
            $adminName = (string) ($user?->name ?? 'Soporte OwOMarket');

            $files = $request->hasFile('attachments') ? (array) $request->file('attachments') : [];

            $message = $this->addMessageUseCase->execute([
                'ticket_id' => $id,
                'sender_type' => 'admin',
                'sender_id' => $adminUserId,
                'sender_name' => $adminName,
                'message' => (string) $request->input('message'),
                'files' => $files,
            ]);

            // Actualizar estado si se solicitó explícitamente
            $status = $request->input('status');
            if ($status) {
                \App\Models\SupportTicket::where('id', $id)->update(['status' => $status]);
            }

            $ticket = $this->detailUseCase->execute($id);

            return ApiResponse::success(
                data: [
                    'ticket' => $ticket,
                    'new_message' => $message,
                ],
                message: 'Respuesta de soporte enviada exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
