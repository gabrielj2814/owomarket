<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\ConsultTenantByUuidOfOwnerUseCase;
use Src\Tenant\Application\UseCase\ConsultTenantsByUuidOfOwnerUseCase;

class ConsultTenantByUuidOfOwnerPOSTController extends Controller {


    public function __construct(
        protected ConsultTenantsByUuidOfOwnerUseCase $consult_tenants_by_uuid_of_owner_use_case
    ){}

    public function index(Request $request){


        $prePage= $request->prePage ?? 50;

        $pagination= $this->consult_tenants_by_uuid_of_owner_use_case->execute($request->id, $prePage);
        // dd($pagination);
        $items=$pagination->getItems();
        $dataPaginate=[];
        for ($i=0; $i < count($items); $i++) {
            $item=$items[$i];
            $dataPaginate[]=[
                "id" =>             $item->getId()->value(),
                "name" =>          $item->getName()->value(),
                "slug" =>          $item->getSlug()->value(),
                "status" =>        $item->getStatus()->value(),
                "timezone" =>      $item->getTimezone()->value(),
                "currency" =>      [
                "code"     =>$item->getCurrency()->code(),
                "name"     =>$item->getCurrency()->name(),
                "symbol"   =>$item->getCurrency()->symbol(),
                "decimals" =>$item->getCurrency()->decimals(),
                // "country"  =>$item->getCurrency()->country(),
                ],
                "request" =>       $item->getRequest()->value(),
                "created_at" =>    $item->getCreatedAt()->value(),
                "updated_at" =>    $item->getUpdatedAt()->value(),
                "deleted_at" =>    $item->getSoftdeleteAt()?->value(),
            ];

            if($item->getDomain()){
                $dataPaginate[$i]["domain"]=[
                    "id" =>         $item->getDomain()->getId()->value(),
                    "domain" =>     $item->getDomain()->getDomain()->value(),
                    "is_primary" => $item->getDomain()->isPrimary()->value(),
                    "is_fallback" => $item->getDomain()->isFallback()->value(),
                ];
            }
        }
        $arrayPagination=$pagination->toArray();
        $arrayPagination["data"]=$dataPaginate;

        return ApiResponse::Pagination(data: $arrayPagination["data"], message: "ok", code: 200, pagination: $arrayPagination["meta"]);


    }



}



?>
