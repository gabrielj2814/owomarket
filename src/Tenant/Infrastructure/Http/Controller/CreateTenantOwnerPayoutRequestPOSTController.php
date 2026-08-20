<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\CreateTenantOwnerPayoutRequestUseCase;

final class CreateTenantOwnerPayoutRequestPOSTController
{
    public function __construct(
        private readonly CreateTenantOwnerPayoutRequestUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|string',
            'tenant_id' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'payment_details' => 'required|array',
            'notes' => 'nullable|string',
        ]);

        try {
            $settlement = $this->useCase->execute(
                (string) $request->input('user_id'),
                [
                    'tenant_id' => (string) $request->input('tenant_id'),
                    'amount' => (float) $request->input('amount'),
                    'payment_method' => (string) $request->input('payment_method'),
                    'payment_details' => (array) $request->input('payment_details'),
                    'notes' => $request->input('notes'),
                ]
            );

            return ApiResponse::success(
                data: [
                    'id' => $settlement->id,
                    'settlement_number' => $settlement->settlement_number,
                    'amount' => (float) $settlement->net_amount,
                    'status' => $settlement->status,
                ],
                message: 'Solicitud de retiro registrada exitosamente.',
                code: 201
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
