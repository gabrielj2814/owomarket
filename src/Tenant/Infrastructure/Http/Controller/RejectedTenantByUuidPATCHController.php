<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\RejectedRequestUseCase;

class RejectedTenantByUuidPATCHController extends Controller {


    function __construct(
        protected RejectedRequestUseCase $rejected_request_use_case
    ){}

    public function index(Request $request, string $id): JsonResponse{

        try {

            $tenant = $this->rejected_request_use_case->execute($id);

            return ApiResponse::success(
                data: $tenant,
                message: "Solicitud de tenant rechazada correctamente",
                code: 200
            );


        } catch (\Throwable $th) {
            return ApiResponse::error(
                message: $th->getMessage(),
                code: $th->getCode() ?: 500
            );
        }
    }




}



?>
