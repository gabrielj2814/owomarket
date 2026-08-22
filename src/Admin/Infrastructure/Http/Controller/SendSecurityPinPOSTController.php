<?php

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\GenerateSecurityPinUseCase;
use Src\Shared\Helper\ApiResponse;

class SendSecurityPinPOSTController extends Controller
{
    private GenerateSecurityPinUseCase $useCase;

    public function __construct(GenerateSecurityPinUseCase $useCase)
    {
        $this->useCase = $useCase;
    }

    public function index(Request $request, string $user_uuid): JsonResponse
    {
        try {
            // Hallazgo A7: se enviaba el PIN al `{user_uuid}` de la URL, asi que cualquier
            // autenticado podia generar PINes contra la cuenta de un admin. Solo puedes
            // pedir un PIN para ti.
            $this->useCase->execute((string) auth()->id());

            return ApiResponse::success(
                data: null,
                message: 'Se ha enviado un PIN de seguridad de 6 dígitos a tu correo electrónico. Es válido por 15 minutos.',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(message: $e->getMessage(), code: 400);
        }
    }
}
