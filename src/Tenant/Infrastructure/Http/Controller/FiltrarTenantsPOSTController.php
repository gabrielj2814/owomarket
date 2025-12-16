<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\FilterTenantUseCase;

class FiltrarTenantsPOSTController extends Controller {


    public function __construct(
        protected FilterTenantUseCase $filter_tenant_use_case
    ){}

    public function index(Request $request){

        $pagination=$this->filter_tenant_use_case->execute();
        // dd($pagination);
        $items=$pagination->getItems();
        $dataPaginate=[];
        for ($i=0; $i < count($items); $i++) {
            $item=$items[$i];
            $dataPaginate[]=[
                "id" =>            $item->getId()->value(),
                "name" =>          $item->getName()->value(),
                "slug" =>          $item->getSlug()->value(),
                "status" =>        $item->getStatus()->value(),
                "timezone" =>      $item->getTimezone()->value(),
                "currency" =>      $item->getCurrency()->code(),
                "request" =>       $item->getRequest()->value(),
                "created_at" =>    $item->getCreatedAt()->value(),
                "updated_at" =>    $item->getUpdatedAt()->value(),
                "deleted_at" =>    $item->getSoftdeleteAt()?->value(),
            ];
        }
        $arrayPagination=$pagination->toArray();
        $arrayPagination["data"]=$dataPaginate;

        return ApiResponse::Pagination(data: $arrayPagination["data"], message: "ok", code: 200, pagination: $arrayPagination["meta"]);


    }





}


?>
