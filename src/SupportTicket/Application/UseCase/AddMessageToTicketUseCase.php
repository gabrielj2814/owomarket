<?php

declare(strict_types=1);

namespace Src\SupportTicket\Application\UseCase;

use Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicket;
use Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicketMessage;
use Exception;
use Illuminate\Support\Str;
use Src\SupportTicket\Application\Service\UploadSupportAttachmentService;

final class AddMessageToTicketUseCase
{
    public function __construct(
        private readonly UploadSupportAttachmentService $uploadService
    ) {}

    /**
     * @param array{
     *     ticket_id: string,
     *     sender_type: 'tenant_owner'|'customer'|'support_agent'|'admin',
     *     sender_id: string,
     *     sender_name: string,
     *     message: string,
     *     is_internal_note?: bool,
     *     files?: array<\Illuminate\Http\UploadedFile>
     * } $data
     * @return SupportTicketMessage
     */
    public function execute(array $data): SupportTicketMessage
    {
        $ticket = SupportTicket::find($data['ticket_id']);
        if (! $ticket) {
            throw new Exception('Ticket de soporte no encontrado.', 404);
        }

        if (empty(trim($data['message']))) {
            throw new Exception('El mensaje no puede estar vacío.', 422);
        }

        $attachments = [];
        if (! empty($data['files'])) {
            $attachments = $this->uploadService->uploadMultiple($data['files']);
        }

        $message = SupportTicketMessage::create([
            'id' => (string) Str::uuid(),
            'ticket_id' => $ticket->id,
            'sender_type' => $data['sender_type'],
            'sender_id' => $data['sender_id'],
            'sender_name' => $data['sender_name'],
            'message' => $data['message'],
            'attachments' => $attachments,
            'is_internal_note' => $data['is_internal_note'] ?? false,
        ]);

        // Actualizar estado del ticket
        $newStatus = in_array($data['sender_type'], ['support_agent', 'admin'], true)
            ? 'waiting_reply'
            : 'in_progress';

        $ticket->update([
            'status' => $newStatus,
            'last_reply_at' => now(),
        ]);

        return $message;
    }
}
