<?php

declare(strict_types=1);

namespace Src\SupportTicket\Application\UseCase;

use App\Models\SupportTicket;

final class ListAdminSupportTicketsUseCase
{
    /**
     * @param array{
     *     requester_type?: string|null,
     *     status?: string|null,
     *     priority?: string|null,
     *     category?: string|null,
     *     search?: string|null,
     *     page?: int,
     *     per_page?: int
     * } $filters
     * @return array{
     *     tickets: array<int, mixed>,
     *     pagination: array{current_page: int, last_page: int, total: int, per_page: int},
     *     metrics: array{
     *         total_open: int,
     *         total_pending: int,
     *         tenant_tickets_count: int,
     *         customer_tickets_count: int,
     *         resolved_count: int
     *     }
     * }
     */
    public function execute(array $filters = []): array
    {
        $query = SupportTicket::with(['messages' => function ($q) {
            $q->latest()->limit(1);
        }])->orderBy('last_reply_at', 'desc')->orderBy('created_at', 'desc');

        if (! empty($filters['requester_type']) && $filters['requester_type'] !== 'all') {
            if ($filters['requester_type'] === 'tenant') {
                $query->whereIn('requester_type', ['tenant_owner', 'staff']);
            } else {
                $query->where('requester_type', $filters['requester_type']);
            }
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('ticket_number', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $paginated = $query->paginate($perPage);

        // Métricas
        $totalOpen = SupportTicket::whereIn('status', ['open', 'in_progress'])->count();
        $totalPending = SupportTicket::where('status', 'waiting_reply')->count();
        $tenantCount = SupportTicket::whereIn('requester_type', ['tenant_owner', 'staff'])->count();
        $customerCount = SupportTicket::where('requester_type', 'customer')->count();
        $resolvedCount = SupportTicket::whereIn('status', ['resolved', 'closed'])->count();

        return [
            'tickets' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
            ],
            'metrics' => [
                'total_open' => $totalOpen,
                'total_pending' => $totalPending,
                'tenant_tickets_count' => $tenantCount,
                'customer_tickets_count' => $customerCount,
                'resolved_count' => $resolvedCount,
            ],
        ];
    }
}
