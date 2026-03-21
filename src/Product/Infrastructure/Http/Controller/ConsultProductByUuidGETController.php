<?php

namespace Src\Product\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Src\Product\Application\UseCase\ConsultProductByUuidUseCase;
use Src\Shared\Helper\ApiResponse;

class ConsultProductByUuidGETController extends Controller {



    public function __construct(
        protected ConsultProductByUuidUseCase $consultProductByUuidUseCase
    ){}

    public function index(Request $request):JsonResponse{


        try {
            $uuid = $request->uuid;
            $product = $this->consultProductByUuidUseCase->excute($uuid);

            if (!$product) {
                return ApiResponse::error(message: 'Product not found', code: 404);
            }

            $data = [
                'id' => $product->getId()->value(),
                'name' => $product->getName()->value(),
                'slug' => $product->getSlug()->value(),
                'price' => $product->getPrice()->value(),
                'sku' => $product->getSku()->value(),
            ];

            return ApiResponse::success(data: $data, message: 'Product retrieved successfully', code: 200);
        } catch (\Exception $e) {
            Log::error('Error consult product: ' . $e->getMessage(), ['exception' => $e]);
            return ApiResponse::error(message: $e->getMessage(), code: ($e->getCode()==0 ? 500 : $e->getCode()));
        }

    }






}


?>
