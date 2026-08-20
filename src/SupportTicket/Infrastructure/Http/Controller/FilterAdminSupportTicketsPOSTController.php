<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\ListAdminSupportTicketsUseCase;

final class FilterAdminSupportTicketsPOSTController
{
    public function __construct(
        private readonly ListAdminSupportTicketsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $filters = [
            'requester_type' => $request->input('requester_type'),
            'status' => $request->input('status'),
            'priority' => $request->input('priority'),
            'category' => $request->input('category'),
            'search' => $request->input('search'),
            'page' => (int) $request->input('page', 1),
            'per_page' => (int) $request->input('per_page', 15),
        ];

        $data = $this->useCase->execute($filters);

        return ApiResponse::success(
            data: $data,
            message: 'Tickets de soporte recuperados exitosamente.'
        );
    }
}
