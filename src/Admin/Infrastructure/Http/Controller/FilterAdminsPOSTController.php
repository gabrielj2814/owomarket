<?php


namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\FilterAdminsUseCase;
use Src\Shared\Helper\ApiResponse;

class FilterAdminsPOSTController extends Controller {



    public function __construct(
        protected FilterAdminsUseCase $filter_admins_use_case
    ){}



    public function index(Request $request): JsonResponse {
        $prePage=$request->prePage;
        $search=null;
        $fechaDesdeUTC=null;
        $fechaHastaUTC=null;
        $status=null;

        if($request->filled("search")){
            $search=$request->search;
        }

        if($request->filled("fechaDesdeUTC")){
            $fechaDesdeUTC=$request->fechaDesdeUTC;
        }

        if($request->filled("fechaHastaUTC")){
            $fechaHastaUTC=$request->fechaHastaUTC;
        }

        if($request->filled("status")){
            $status=$request->status;
        }

        $pagination=$this->filter_admins_use_case->excute($search, $fechaDesdeUTC, $fechaHastaUTC, $status, $prePage);
        // dd($pagination);
        $items=$pagination->getItems();
        $dataPaginate=[];
        for ($i=0; $i < count($items); $i++) {
            $item=$items[$i];
            $dataPaginate[]=[
                "id" =>          $item->getId()->value(),
                "name" =>        $item->getName()->value(),
                "email" =>       $item->getEmail()->value(),
                "phone" =>       $item->getPhone()?->value(),
                "type" =>        $item->getType()->value(),
                "avatar" =>      $item->getAvatar()?->value(),
                "is_active" =>   $item->isActive(),
                "created_at" =>  $item->getCreatedAt()?->value(),
                "updated_at" =>  $item->getUpdatedAt()?->value(),
            ];
        }
        $arrayPagination=$pagination->toArray();
        $arrayPagination["data"]=$dataPaginate;

        return ApiResponse::Pagination(data: $arrayPagination["data"], message: "ok", code: 200, pagination: $arrayPagination["meta"]);

    }


}


?>
