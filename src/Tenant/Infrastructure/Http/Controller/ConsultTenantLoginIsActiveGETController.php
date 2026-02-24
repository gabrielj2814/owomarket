<?php

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\ConsultTenantLoginIsActiveUseCase;

class ConsultTenantLoginIsActiveGETController extends Controller {

    public function __construct(
        protected ConsultTenantLoginIsActiveUseCase $consultTenantLoginIsActiveUseCase
    ){}


    public function index (Request $request, string $slug){

        try {
            $domain = $request->getHost();
            $response = $this->consultTenantLoginIsActiveUseCase->execute($slug, $domain);
            return ApiResponse::success(data: $response);

        } catch (\Throwable $th) {
            return ApiResponse::error(message: $th->getMessage(), code: $th->getCode() === 0 ? 500 : $th->getCode());
        }

    }





}


?>
