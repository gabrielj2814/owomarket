<?php

declare(strict_types=1);

namespace Src\SupportTicket\Application\UseCase;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Exception;
use Illuminate\Support\Str;
use Src\SupportTicket\Application\Service\UploadSupportAttachmentService;

final class CreateSupportTicketUseCase
{
    public function __construct(
        private readonly UploadSupportAttachmentService $uploadService
    ) {}

    /**
     * @param array{
     *     user_id: string,
     *     requester_type: 'tenant_owner'|'customer'|'guest'|'staff',
     *     sender_name?: string,
     *     tenant_id?: string|null,
     *     category: string,
     *     priority?: 'low'|'medium'|'high'|'urgent',
     *     subject: string,
     *     description: string,
     *     metadata?: array<string, mixed>,
     *     files?: array<\Illuminate\Http\UploadedFile>
     * } $data
     * @return SupportTicket
     */
    public function execute(array $data): SupportTicket
    {
        if (empty($data['subject']) || empty($data['description'])) {
            throw new Exception('El asunto y la descripción son obligatorios para generar un ticket.', 422);
        }

        // Subir adjuntos multimedia si existen
        $attachments = [];
        if (! empty($data['files'])) {
            $attachments = $this->uploadService->uploadMultiple($data['files']);
        }

        $ticketNumber = 'TKT-'.date('Ymd').'-'.strtoupper(Str::random(6));

        $ticket = SupportTicket::create([
            'id' => (string) Str::uuid(),
            'ticket_number' => $ticketNumber,
            'requester_type' => $data['requester_type'],
            'user_id' => $data['user_id'],
            'tenant_id' => $data['tenant_id'] ?? null,
            'category' => $data['category'] ?? 'technical_error',
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'subject' => $data['subject'],
            'description' => $data['description'],
            'attachments' => $attachments,
            'metadata' => $data['metadata'] ?? [],
            'last_reply_at' => now(),
        ]);

        // Registrar primer mensaje en el hilo
        SupportTicketMessage::create([
            'id' => (string) Str::uuid(),
            'ticket_id' => $ticket->id,
            'sender_type' => $data['requester_type'],
            'sender_id' => $data['user_id'],
            'sender_name' => $data['sender_name'] ?? 'Usuario',
            'message' => $data['description'],
            'attachments' => $attachments,
            'is_internal_note' => false,
        ]);

        return $ticket->fresh(['messages']);
    }
}
