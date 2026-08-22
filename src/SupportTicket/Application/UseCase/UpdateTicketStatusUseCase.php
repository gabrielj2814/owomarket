<?php

declare(strict_types=1);

namespace Src\SupportTicket\Application\UseCase;

use Exception;
use Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicket;

final class UpdateTicketStatusUseCase
{
    /**
     * @param  string|null  $requesterId  ID resuelto de la sesión de quien hace la petición.
     *                                    Null = ruta de staff (UpdateAdminSupportTicketStatusPATCHController),
     *                                    que puede tocar cualquier ticket. Si viene informado, se
     *                                    exige que sea el dueño (antes cualquiera podía cerrar o
     *                                    reabrir el ticket de otra persona, hallazgo A6).
     */
    public function execute(string $ticketId, string $status, ?string $requesterId = null): SupportTicket
    {
        $allowed = ['open', 'in_progress', 'waiting_reply', 'resolved', 'closed'];
        if (! in_array($status, $allowed, true)) {
            throw new Exception('Estado inválido. Permitidos: '.implode(', ', $allowed), 422);
        }

        $ticket = SupportTicket::find($ticketId);
        if (! $ticket) {
            throw new Exception('Ticket de soporte no encontrado.', 404);
        }

        if ($requesterId !== null && (string) $ticket->user_id !== $requesterId) {
            throw new Exception('No tienes acceso a este ticket de soporte.', 403);
        }

        $ticket->update(['status' => $status]);

        return $ticket;
    }
}
