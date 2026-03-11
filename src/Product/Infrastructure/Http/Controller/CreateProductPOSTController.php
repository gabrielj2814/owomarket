<?php

namespace Src\Product\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Src\Product\Application\UseCase\CreateProductUseCase;
use Src\Product\Infrastructure\Http\Request\CreateProductFormRequest;
use Src\Shared\Helper\ApiResponse;

class CreateProductPOSTController extends Controller {

    public function __construct(
        protected CreateProductUseCase $createProductUseCase
    ){}

    public function index(CreateProductFormRequest $request): JsonResponse {
        $data= $request->data;
        $name = $data->name;
        $slug = $data->slug;
        $price = $data->price;
        $sku = $data->sku;

        try {
            $product = $this->createProductUseCase->execute(
                name: $name,
                slug: $slug,
                price: $price,
                sku: $sku
            );

            return ApiResponse::success(data: $product, message: 'Product created successfully', code: 200);
        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage(), ['exception' => $e]);
            return ApiResponse::error(message: $e->getMessage(), code: ($e->getCode()==0 ? 500 : $e->getCode()));
        }
    }



}



?>
