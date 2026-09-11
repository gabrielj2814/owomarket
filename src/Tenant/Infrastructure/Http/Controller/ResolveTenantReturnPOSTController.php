<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\ResolveReturnRequestUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;

/**
 * El comerciante resuelve una reclamacion de su tienda (subsistema 5, fase A).
 *
 * Es la mitad que faltaba: la tabla existia desde agosto y **el cliente creaba solicitudes que
 * nadie podia resolver**, porque no habia ninguna ruta que cambiara su estado.
 *
 * La propiedad se comprueba contra la TIENDA de la reclamacion, no contra lo que venga en la
 * peticion. Sin eso, un comerciante podria rechazar las reclamaciones de otro -- o aprobarlas,
 * que le costaria dinero a un tercero.
 */
final class ResolveTenantReturnPOSTController
{
    public function __construct(
        private readonly ResolveReturnRequestUseCase $useCase,
        private readonly TenantOwnershipVerifier $ownership
    ) {}

    public function __invoke(Request $request, string $returnId): JsonResponse
    {
        $request->validate([
            'approved' => 'required|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $userId = (string) (auth()->id() ?? '');

        if ($userId === '') {
            return ApiResponse::error('Debes iniciar sesión.', 401);
        }

        $reclamacion = CustomerReturnRequest::find($returnId);

        if ($reclamacion === null) {
            return ApiResponse::error('Reclamación no encontrada.', 404);
        }

        // 404 si no existe y 403 si es de otra tienda: lo resuelve el verificador.
        $this->ownership->ensureOwns($userId, $reclamacion->tenant_id);

        try {
            $resuelta = $this->useCase->execute(
                $returnId,
                aprobada: $request->boolean('approved'),
                resolvedBy: 'merchant',
                notas: $request->input('notes')
            );

            return ApiResponse::success([
                'status' => $resuelta->status,
                'resolved_at' => $resuelta->resolved_at?->toIso8601String(),
            ], $resuelta->status === 'approved'
                ? 'Reclamación aprobada. El importe se devuelve al comprador.'
                : 'Reclamación rechazada.');
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 400;

            return ApiResponse::error($e->getMessage(), $code >= 400 && $code < 600 ? $code : 400);
        }
    }
}
