<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\SupportTicket\Application\Service\UploadSupportAttachmentService;
use Src\Tenant\Application\UseCase\SubmitTenantKycUseCase;
use Src\Tenant\Infrastructure\Http\Request\SubmitTenantKycFormRequest;

/**
 * El comerciante envia su expediente de identidad (subsistema 1).
 *
 * El usuario sale SIEMPRE de la sesion, nunca del cuerpo: es el hallazgo A2, por el que un
 * anonimo llego a poder crear solicitudes de retiro contra cualquier tienda con sus propios
 * datos bancarios. Aqui el riesgo es el simetrico -- subirle documentos falsos al expediente
 * de otro.
 */
final class SubmitTenantKycPOSTController
{
    public function __construct(
        private readonly SubmitTenantKycUseCase $useCase,
        private readonly UploadSupportAttachmentService $uploader
    ) {}

    public function __invoke(SubmitTenantKycFormRequest $request, string $tenantId): JsonResponse
    {
        $userId = (string) (auth()->id() ?? '');

        if ($userId === '') {
            return ApiResponse::error('Debes iniciar sesión.', 401);
        }

        $datos = $request->validated();

        if ($request->hasFile('document')) {
            // Carpeta propia: son documentos de identidad y no pueden acabar mezclados con los
            // adjuntos de soporte, que tienen otra politica de acceso y de retencion.
            $datos['document_path'] = $this->uploader
                ->uploadSingle($request->file('document'), 'kyc-documents')['url'];
        }

        try {
            $perfil = $this->useCase->execute($userId, $tenantId, $datos);

            return ApiResponse::success([
                'status' => $perfil->status,
                'submitted_at' => $perfil->updated_at?->toIso8601String(),
            ], 'Datos enviados. Te avisaremos en cuanto verifiquemos tu identidad.');
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 400;

            return ApiResponse::error($e->getMessage(), $code >= 400 && $code < 600 ? $code : 400);
        }
    }
}
