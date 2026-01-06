<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\ActiveTenantByUuidUseCase;
use Src\Tenant\Application\UseCase\InactiveTenantByUuidUseCase;

class InactiveTenantByUuidPATCHController extends Controller {


    public function __construct(
        protected InactiveTenantByUuidUseCase $inactive_tenant_by_uuid_use_case
    ){}

    public function index(Request $request):JsonResponse {

        try {

            $uuid=$request->id;

            $respuestaUseCase=$this->inactive_tenant_by_uuid_use_case->execute($uuid);

            return ApiResponse::success(data:null, message:"Operación exitosa", code: 200);

        } catch (\Throwable $th) {
            //throw $th;
            return ApiResponse::error(message:$th->getMessage(), code: $th->getCode());
        }


    }



}



?>
