<?php

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\ChangeStatuAdminByUuidUseCase;
use Src\Admin\Application\UseCase\ConsultAdminByUuidUseCase;
use Src\Admin\Domain\ValueObjects\UserStatus;
use Src\Admin\Domain\ValueObjects\Uuid;
use Src\Admin\Infrastructure\Http\Request\ChangeStatuAdminFormRequest;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\Request;

class ChangeStatuAdminByUuidPATCHController extends Controller {

    public function __construct(
        protected ConsultAdminByUuidUseCase $consult_admin_by_uuid_use_case,
        protected ChangeStatuAdminByUuidUseCase $change_statu_admin_by_uuid_use_case
    ){}




    public function index(ChangeStatuAdminFormRequest $request): JsonResponse {

        $data=$request->data;

        $uuid= Uuid::make($data->id);
        $statu= UserStatus::make($data->statu);

        $validarExistencia=$this->consult_admin_by_uuid_use_case->execute($uuid);
        if(!$validarExistencia){
            return ApiResponse::error(message: "Error el admin no fue encontrado en la base de dato", code: 404);
        }

        $this->change_statu_admin_by_uuid_use_case->execute($uuid, $statu);

        return ApiResponse::success(data: null, message:"OK");
    }


}



?>
