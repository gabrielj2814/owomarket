<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\CancelAccountTenantOwnerUseCase;

class CancelAccountTenantOwnerDELETEController extends Controller {



    public function __construct(
        protected CancelAccountTenantOwnerUseCase $cancel_account_tenant_owner_use_case
    ){}

    public function index(Request $request, string $id):JsonResponse {
        try {
            //code...
            $this->cancel_account_tenant_owner_use_case->execute($id);

            return ApiResponse::success(data: null, message: "ok", code: 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::info("Error");
            Log::info($th->getCode());
            Log::error($th->getMessage());
            $code=($th->getCode()!=0)?500:$th->getCode();
            return ApiResponse::error(message:$th->getMessage(), code: $code);
        }



    }



}



?>
