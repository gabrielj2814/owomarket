<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\SupportTicket\Application\UseCase\ListAdminSupportTicketsUseCase;

final class ViewAdminSupportTicketsPageGETController
{
    public function __construct(
        private readonly ListAdminSupportTicketsUseCase $useCase
    ) {}

    public function index(Request $request, string $user_uuid): Response
    {
        $filters = [
            'requester_type' => $request->query('requester_type'),
            'status' => $request->query('status'),
            'priority' => $request->query('priority'),
            'category' => $request->query('category'),
            'search' => $request->query('search'),
            'page' => (int) $request->query('page', 1),
            'per_page' => 15,
        ];

        $result = $this->useCase->execute($filters);

        return Inertia::render('admin/support/AdminSupportTicketsPage', [
            'title' => 'Mesa Central de Soporte y Tickets - OwOMarket Admin',
            'user_id' => $user_uuid,
            'tickets' => $result['tickets'],
            'pagination' => $result['pagination'],
            'metrics' => $result['metrics'],
            'filters' => $filters,
        ]);
    }
}
