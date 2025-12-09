<?php


namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ConsultAdminByUuidUseCase;
use Src\Admin\Application\UseCase\DeleteAdminByUuidUseCase;
use Src\Admin\Domain\ValueObjects\Uuid;
use Src\Shared\Helper\ApiResponse;

class DeleteAdminByUuidDELETEController extends Controller {


    public function __construct(
        protected DeleteAdminByUuidUseCase $delete_admin_by_uuid_use_case,
        protected ConsultAdminByUuidUseCase $consult_admin_by_uuid_use_case
    ){}



    public function index(Request $request):JsonResponse {

        $uuid=Uuid::make($request->uuid);

        $validarExistencia=$this->consult_admin_by_uuid_use_case->execute($uuid);
        if(!$validarExistencia){
            return ApiResponse::error(message:"Error: no se pudo eliminar el admintrador por que no se encontro en la base de datos", code: 404);
        }

        $this->delete_admin_by_uuid_use_case->execute($uuid);

        $verificarEliminacion=$this->consult_admin_by_uuid_use_case->execute($uuid);
        if($verificarEliminacion){
            return ApiResponse::error(message:"Error: El admin no se pudo eliminar consulte con soporte", code: 400);
        }

        return ApiResponse::success(data:null, message:"ok", code: 200);






    }





}


?>
