<?php


namespace Src\Product\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Src\Product\Application\UseCase\DeleteProductByUuidUseCase;
use Src\Shared\Helper\ApiResponse;

class DeleteProductByUuidDELETEController extends Controller {

    public function __construct(
        protected DeleteProductByUuidUseCase $deleteProductByUuidUseCase
    ){}

    public function index(Request $request){
        try {
            $uuid = $request->uuid;
            $this->deleteProductByUuidUseCase->excute($uuid);


            return ApiResponse::success(data: null, message: 'Product deleted successfully', code: 200);
        } catch (\Exception $e) {
            Log::error('Error delete product: ' . $e->getMessage(), ['exception' => $e]);
            return ApiResponse::error(message: $e->getMessage(), code: ($e->getCode()==0 ? 500 : $e->getCode()));
        }
    }



}


?>
