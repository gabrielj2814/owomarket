<?php

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\ActiveTenantByUuidUseCase;

class ActiveTenantByUuidPATCHController extends Controller
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected ActiveTenantByUuidUseCase $active_tenant_by_uuid_use_case
    ) {}

    /**
     * Método index.
     */
    public function index(Request $request): JsonResponse
    {

        try {

            $uuid = $request->id;

            $respuestaUseCase = $this->active_tenant_by_uuid_use_case->execute($uuid);

            return ApiResponse::success(data: null, message: 'Operación exitosa', code: 200);

        } catch (\Throwable $th) {
            // throw $th;
            return ApiResponse::error(message: $th->getMessage(), code: $th->getCode());
        }

    }
}
