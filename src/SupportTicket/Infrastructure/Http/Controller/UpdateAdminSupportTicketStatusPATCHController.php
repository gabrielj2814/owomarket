<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\UseCase\GetSupportTicketDetailUseCase;

final class UpdateAdminSupportTicketStatusPATCHController
{
    public function __construct(
        private readonly GetSupportTicketDetailUseCase $detailUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:open,in_progress,waiting_reply,resolved,closed',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
        ]);

        try {
            $updates = [];
            if ($request->has('status')) {
                $updates['status'] = (string) $request->input('status');
            }
            if ($request->has('priority')) {
                $updates['priority'] = (string) $request->input('priority');
            }

            if (! empty($updates)) {
                \Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicket::where('id', $id)->update($updates);
            }

            $ticket = $this->detailUseCase->execute($id);

            return ApiResponse::success(
                data: $ticket,
                message: 'Estado del ticket actualizado exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
