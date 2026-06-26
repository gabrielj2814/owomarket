<?php

namespace Src\Product\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Src\Product\Application\UseCase\EditProductByUuidUseCase;
use Src\Product\Infrastructure\Http\Request\EditProductFomrRequest;
use Src\Shared\Helper\ApiResponse;

class EditProductByUuidPUTController extends Controller {


    /**
     * Constructor de la clase.
     */


    public function __construct(
        protected EditProductByUuidUseCase $editProductByUuidUseCase
    ){}

    /**
     * Método index.
     */

    public function index(EditProductFomrRequest $request){
        try {
            $dataForm= $request->data;

            $uuid= $request->uuid;

            $product = $this->editProductByUuidUseCase->excute(
                $uuid,
                $dataForm->name,
                $dataForm->price,
                $dataForm->sku,
                $dataForm->slug
            );

            $dataResponse = [
                'id' => $product->getId()->value(),
                'name' => $product->getName()->value(),
                'slug' => $product->getSlug()->value(),
                'price' => $product->getPrice()->value(),
                'sku' => $product->getSku()->value(),
            ];

            return ApiResponse::success(data: $dataResponse, message: 'Product updated successfully', code: 200);
        } catch (\Exception $e) {
            Log::error('Error update product: ' . $e->getMessage(), ['exception' => $e]);
            return ApiResponse::error(message: $e->getMessage(), code: ($e->getCode()==0 ? 500 : $e->getCode()));
        }
    }



}


?>
