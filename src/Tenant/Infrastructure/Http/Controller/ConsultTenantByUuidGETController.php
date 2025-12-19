<?php

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\ConsultTenantByUuidUseCase;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class ConsultTenantByUuidGETController extends Controller{



    public function __construct(
        protected ConsultTenantByUuidUseCase $consult_tenant_by_uuid_use_case
    ){}

    public function index(Request $request): JsonResponse{
        try {
            $uuid= Uuid::make($request->id);

            $searchTenant= $this->consult_tenant_by_uuid_use_case->execute($uuid);

            $owners=[];
            for ($i=0; $i < count($searchTenant->getOwners()); $i++) {
                # code...
                $itemOwner=$searchTenant->getOwners()[$i];
                $owner=[
                    "id" =>          $itemOwner->getId()->value(),
                    "name" =>        $itemOwner->getName()->value(),
                    "email" =>       $itemOwner->getEmail()->value(),
                    "phone" =>       $itemOwner->getPhone()?->value(),
                    "type" =>        $itemOwner->getType()->value(),
                    "avatar" =>      $itemOwner->getAvatar()?->value(),
                    "is_active" =>   $itemOwner->isActive(),
                    "created_at" =>  $itemOwner->getCreatedAt()?->value(),
                    "updated_at" =>  $itemOwner->getUpdatedAt()?->value(),
                ];
                $owners[]=$owner;
            }

            $tenant=[
                "id" =>            $searchTenant->getId()->value(),
                    "name" =>          $searchTenant->getName()->value(),
                    "slug" =>          $searchTenant->getSlug()->value(),
                    "status" =>        $searchTenant->getStatus()->value(),
                    "timezone" =>      $searchTenant->getTimezone()->value(),
                    "currency" =>      [
                        "code"     =>$searchTenant->getCurrency()->code(),
                        "name"     =>$searchTenant->getCurrency()->name(),
                        "symbol"   =>$searchTenant->getCurrency()->symbol(),
                        "decimals" =>$searchTenant->getCurrency()->decimals(),
                        // "country"  =>$searchTenant->getCurrency()->country(),
                    ],
                    "request" =>       $searchTenant->getRequest()->value(),
                    "created_at" =>    $searchTenant->getCreatedAt()->value(),
                    "updated_at" =>    $searchTenant->getUpdatedAt()->value(),
                    "deleted_at" =>    $searchTenant->getSoftdeleteAt()?->value(),
                    "owners" => $owners,
            ];

            return ApiResponse::success(data: $tenant, message:"OK", code: 200);
        } catch (\Throwable $th) {
            //throw $th;

            return ApiResponse::error(message:$th->getMessage(), code: $th->getCode());
        }

    }








}






?>
