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
            'tenant_id' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'payment_details' => 'required|array',
            'notes' => 'nullable|string',
        ]);

        // La identidad SIEMPRE sale de la sesión. Antes se aceptaba 'user_id' del cuerpo,
        // lo que permitía a un anónimo crear solicitudes de retiro contra cualquier
        // tienda, con sus propios datos bancarios (hallazgo A2).
        $userId = (string) (auth()->id() ?? '');

        if ($userId === '') {
            return ApiResponse::error('Debes iniciar sesión para solicitar un retiro.', 401);
        }

        try {
            $settlement = $this->useCase->execute(
                $userId,
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
