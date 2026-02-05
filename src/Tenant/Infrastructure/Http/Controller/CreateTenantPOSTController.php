<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\CreateTenantUseCase;
use Src\Tenant\Application\UseCase\CreateTenantUserUseCase;
use Src\Tenant\Infrastructure\Http\Request\CreateTenantFormRequest;

class CreateTenantPOSTController extends Controller {


    public function __construct(
        protected CreateTenantUseCase $create_tenant_use_case,
        protected CreateTenantUserUseCase $create_tenant_user_use_case
    ){}

    public function index(CreateTenantFormRequest $request): JsonResponse{

        try {
            //code...
            $tenant=$this->create_tenant_use_case->execute(
                $request->store_name
            );

            $tenantUser=$this->create_tenant_user_use_case->execute(
                $request->id,
                $tenant->getId()->value()
            );

            return ApiResponse::success(data: [], message: "Tenant created successfully", code: 201);

        } catch (\Throwable $th) {
            //throw $th;
            Log::info("error:");
            Log::info($th->getMessage());
            return ApiResponse::error(message:$th->getMessage(), code: $th->getCode());
        }


    }


}


?>
