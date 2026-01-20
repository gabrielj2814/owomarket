<?php

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\UpdatePersonalDataUseCase;
use Src\Tenant\Infrastructure\Http\Request\UpdatePersonalDataFormRequest;

class TenantUpdatePersonalDataPUTController extends Controller{


    public function __construct(
        protected UpdatePersonalDataUseCase $update_personal_data_use_case,
    ){}


    public function index(UpdatePersonalDataFormRequest $request, string $id):JsonResponse{
        try {

            $name=$request->data->name;
            $phone=$request->data->phone;

            $tenantOwner=$this->update_personal_data_use_case->execute($id,$name,$phone);

            return ApiResponse::success(data: null, message: "ok");

        } catch (\Throwable $th) {

            $code=($th->getCode()==0)?500:$th->getCode();
            return ApiResponse::error(message:$th->getMessage(), code: $code);
        }



    }



}


?>
