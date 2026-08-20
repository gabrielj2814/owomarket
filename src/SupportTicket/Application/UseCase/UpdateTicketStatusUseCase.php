<?php

declare(strict_types=1);

namespace Src\SupportTicket\Application\UseCase;

use App\Models\SupportTicket;
use Exception;

final class UpdateTicketStatusUseCase
{
    /**
     * @return SupportTicket
     */
    public function execute(string $ticketId, string $status): SupportTicket
    {
        $allowed = ['open', 'in_progress', 'waiting_reply', 'resolved', 'closed'];
        if (! in_array($status, $allowed, true)) {
            throw new Exception("Estado inválido. Permitidos: ".implode(', ', $allowed), 422);
        }

        $ticket = SupportTicket::find($ticketId);
        if (! $ticket) {
            throw new Exception('Ticket de soporte no encontrado.', 404);
        }

        $ticket->update(['status' => $status]);

        return $ticket;
    }
}
