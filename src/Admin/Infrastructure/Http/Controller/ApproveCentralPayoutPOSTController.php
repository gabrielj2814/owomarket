<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ApproveCentralPayoutRequestUseCase;
use Src\Shared\Helper\ApiResponse;

final class ApproveCentralPayoutPOSTController
{
    public function __construct(
        private readonly ApproveCentralPayoutRequestUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'payment_reference' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $adminUserId = (string) (auth()->id() ?? 'system');

            $settlement = $this->useCase->execute(
                settlementId: $id,
                adminUserId: $adminUserId,
                data: [
                    'payment_reference' => (string) $request->input('payment_reference'),
                    'notes' => $request->input('notes'),
                ]
            );

            return ApiResponse::success(
                data: [
                    'id' => $settlement->id,
                    'settlement_number' => $settlement->settlement_number,
                    'status' => $settlement->status,
                    'payment_reference' => $settlement->payment_reference,
                    'settled_at' => $settlement->settled_at?->toIso8601String(),
                ],
                message: 'Solicitud de retiro aprobada y liquidada exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
