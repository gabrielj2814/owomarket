<?php

declare(strict_types=1);

namespace Src\SupportTicket\Application\UseCase;

use App\Models\SupportTicket;

final class ListUserSupportTicketsUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $userId, ?string $status = null, ?string $tenantId = null): array
    {
        $query = SupportTicket::with('messages')
            ->where('user_id', $userId);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($status && in_array($status, ['open', 'in_progress', 'waiting_reply', 'resolved', 'closed'], true)) {
            $query->where('status', $status);
        }

        $tickets = $query->orderBy('updated_at', 'desc')->paginate(15);

        $counts = [
            'total' => SupportTicket::where('user_id', $userId)->count(),
            'open' => SupportTicket::where('user_id', $userId)->where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('user_id', $userId)->where('status', 'in_progress')->count(),
            'waiting_reply' => SupportTicket::where('user_id', $userId)->where('status', 'waiting_reply')->count(),
            'resolved' => SupportTicket::where('user_id', $userId)->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        return [
            'tickets' => $tickets->items(),
            'counts' => $counts,
            'pagination' => [
                'total' => $tickets->total(),
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
            ],
        ];
    }
}
