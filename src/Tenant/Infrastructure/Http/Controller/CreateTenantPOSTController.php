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
        $centralDomain = env('APP_CENTRAL_DOMAIN', 'owomarket.com');
        try {
            //code...
            $tenant=$this->create_tenant_use_case->execute(
                $request->store_name,
                $centralDomain
            );

            $tenantUser=$this->create_tenant_user_use_case->execute(
                $request->id,
                $tenant->getId()->value()
            );


            $data= [
                "tenant"=>[
                    "id" =>            $tenant->getId()->value(),
                    "name" =>          $tenant->getName()->value(),
                    "slug" =>          $tenant->getSlug()->value(),
                    "status" =>        $tenant->getStatus()->value(),
                    "timezone" =>      $tenant->getTimezone()->value(),
                    "currency" =>      [
                        "code"     =>$tenant->getCurrency()->code(),
                        "name"     =>$tenant->getCurrency()->name(),
                        "symbol"   =>$tenant->getCurrency()->symbol(),
                        "decimals" =>$tenant->getCurrency()->decimals(),
                        // "country"  =>$tenant->getCurrency()->country(),
                    ],
                    "request" =>       $tenant->getRequest()->value(),
                    "created_at" =>    $tenant->getCreatedAt()->value(),
                    "updated_at" =>    $tenant->getUpdatedAt()?->value(),
                    "deleted_at" =>    $tenant->getSoftdeleteAt()?->value(),
                ],
                "tenant_user"=>[
                    "id"=> $tenantUser->getId()->value(),
                    "user_id"=> $tenantUser->getUserId()->value(),
                    "tenant_id"=> $tenantUser->getTenantId()->value(),
                    "role"=> $tenantUser->getRole()->value(),
                    "created_at"=> $tenantUser->getCreatedAt()->value(),
                    "updated_at"=> $tenantUser->getUpdatedAt()?->value(),
                ]
            ];

            return ApiResponse::success(data: $data, message: "Tenant created successfully", code: 200);

        } catch (\Throwable $th) {
            //throw $th;
            Log::info("error:");
            Log::info($th->getMessage());
            return ApiResponse::error(message:$th->getMessage(), code: $th->getCode());
        }


    }


}


?>
