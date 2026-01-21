<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\UpdatePasswordTenantOwnerUseCase;

class TenantOwnerUpdatePasswordPUTController extends Controller {



    public function __construct(
        protected UpdatePasswordTenantOwnerUseCase $update_password_tenant_owner_use_case
    ){}



    public function index(Request $request, string $id): JsonResponse{
        try {
            //code...
            $claveVieja=$request->claveVieja;
            $claveNueva=$request->claveNueva;
            $tenantOwner= $this->update_password_tenant_owner_use_case->execute($id, $claveVieja, $claveNueva);

            return ApiResponse::success(data: null, message: "Ok", code: 200);

        } catch (\Throwable $th) {
            //throw $th;
            $code=($th->getCode()==0)?500:$th->getCode();
            return ApiResponse::error(message:$th->getMessage(), code: $code);
        }
    }



}




?>
