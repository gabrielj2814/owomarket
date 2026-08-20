<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\RejectCentralPayoutRequestUseCase;
use Src\Shared\Helper\ApiResponse;

final class RejectCentralPayoutPOSTController
{
    public function __construct(
        private readonly RejectCentralPayoutRequestUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $adminUserId = (string) (auth()->id() ?? 'system');

            $settlement = $this->useCase->execute(
                settlementId: $id,
                adminUserId: $adminUserId,
                data: [
                    'rejection_reason' => (string) $request->input('rejection_reason'),
                    'notes' => $request->input('notes'),
                ]
            );

            return ApiResponse::success(
                data: [
                    'id' => $settlement->id,
                    'settlement_number' => $settlement->settlement_number,
                    'status' => $settlement->status,
                    'notes' => $settlement->notes,
                ],
                message: 'Solicitud de retiro rechazada exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
