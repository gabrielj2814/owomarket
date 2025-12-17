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

        $search=null;
        $fechaDesdeUTC=null;
        $fechaHastaUTC=null;
        $status=null;
        $tenantRequest=null;
        $prePage=null;

        if($request->filled("search")){
            $search=$request->search;
        }

        if($request->filled("fechaDesdeUTC") && $request->filled("fechaHastaUTC")){
            $fechaDesdeUTC=$request->fechaDesdeUTC;
            $fechaHastaUTC=$request->fechaHastaUTC;
        }

        if($request->filled("status")){
            $status=$request->status;
        }

        if($request->filled("tenantRequest")){
            $tenantRequest=$request->tenantRequest;
        }

        if($request->filled("prePage")){
            $prePage=$request->prePage;
        }
        else{
            $prePage=50;
        }

        $pagination=$this->filter_tenant_use_case->execute(
            $search,
            $fechaDesdeUTC,
            $fechaHastaUTC,
            $status,
            $tenantRequest,
            $prePage
        );
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
        }
        $arrayPagination=$pagination->toArray();
        $arrayPagination["data"]=$dataPaginate;

        return ApiResponse::Pagination(data: $arrayPagination["data"], message: "ok", code: 200, pagination: $arrayPagination["meta"]);


    }





}


?>
