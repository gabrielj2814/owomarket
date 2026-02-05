<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\DeleteTenantByUuidUseCase;

class DeleteTenantDELETEController extends Controller {


    public function __construct(
        protected DeleteTenantByUuidUseCase $delete_tenant_by_uuid_use_case
    ){}

    public function index(Request $request): JsonResponse{
        try {
            //code...
            $idTenant= $request->idTenant;
            $idTenantOwner= $request->idTenantOwner; // id del owner hay que obtenerlo de la sesion del usuario autenticado
            $this->delete_tenant_by_uuid_use_case->execute($idTenant, $idTenantOwner);
            return ApiResponse::success(message: 'Tenant deleted successfully', code: 200);

        } catch (\Throwable $th) {
            //throw $th;
            return ApiResponse::error(message: $th->getMessage(), code: 500);
        }
    }


}


?>
