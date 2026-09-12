<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ReviewTenantKycUseCase;
use Src\Shared\Helper\ApiResponse;

/**
 * El administrador verifica o rechaza un expediente (subsistema 1).
 *
 * **Esta ruta es la que desbloquea el cobro de todas las tiendas.** El caso de uso existía
 * desde que se construyó el subsistema; lo que faltaba era exactamente esto.
 *
 * El motivo del rechazo se valida DOS veces: aquí, para que el frontend reciba un 422 con el
 * campo señalado, y dentro del caso de uso, porque `ReviewTenantKycUseCase` también se invoca
 * desde otros sitios --el seeder, un comando-- y la regla «rechazar exige motivo» pertenece al
 * negocio, no a este formulario.
 */
final class ReviewTenantKycPOSTController
{
    public function __construct(
        private readonly ReviewTenantKycUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $profileId): JsonResponse
    {
        $request->validate([
            'approved' => 'required|boolean',
            'reason' => 'required_if:approved,false|nullable|string|max:1000',
        ], [
            'reason.required_if' => 'Indica el motivo del rechazo: el comerciante necesita saber qué corregir.',
        ]);

        try {
            $perfil = $this->useCase->execute(
                profileId: $profileId,
                adminId: (string) (auth()->id() ?? 'system'),
                aprobado: $request->boolean('approved'),
                motivo: $request->input('reason')
            );

            return ApiResponse::success(
                data: [
                    'id' => $perfil->id,
                    'status' => $perfil->status,
                    'rejection_reason' => $perfil->rejection_reason,
                    'reviewed_at' => $perfil->reviewed_at?->toIso8601String(),
                    'reviewed_by' => $perfil->reviewed_by,
                ],
                message: $perfil->status === 'verified'
                    ? 'Identidad verificada. La tienda ya puede solicitar retiros.'
                    : 'Expediente rechazado. El comerciante verá el motivo en su billetera.'
            );
        } catch (Exception $e) {
            $codigo = (int) $e->getCode();

            return ApiResponse::error(
                message: $e->getMessage(),
                code: $codigo >= 400 && $codigo < 600 ? $codigo : 400
            );
        }
    }
}
