<?php

declare(strict_types=1);

namespace Src\SupportTicket\Application\UseCase;

use Exception;
use Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicket;

final class GetSupportTicketDetailUseCase
{
    public function execute(string $ticketId, ?string $userId = null): SupportTicket
    {
        $query = SupportTicket::with(['messages']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $ticket = $query->find($ticketId);

        if (! $ticket) {
            // Intentar buscar por ticket_number
            $ticket = SupportTicket::with(['messages'])
                ->where('ticket_number', $ticketId)
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->first();
        }

        if (! $ticket) {
            throw new Exception('Ticket de soporte no encontrado o sin permisos de acceso.', 404);
        }

        return $ticket;
    }
}
