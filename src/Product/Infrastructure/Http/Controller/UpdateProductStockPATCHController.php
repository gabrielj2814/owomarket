<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Product\Application\UseCase\UpdateProductStockUseCase;
use Src\Product\Infrastructure\Http\Request\UpdateProductStockFormRequest;
use Src\Shared\Helper\ApiResponse;

final class UpdateProductStockPATCHController
{
    public function __construct(
        private readonly UpdateProductStockUseCase $useCase
    ) {}

    public function __invoke(string $id, UpdateProductStockFormRequest $request): JsonResponse
    {
        try {
            $quantity = (int) $request->input('quantity');
            $variantId = $request->input('variant_id');

            $this->useCase->execute($id, $quantity, $variantId ? (string) $variantId : null);

            return ApiResponse::success(
                data: null,
                message: 'Stock del producto actualizado exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            /*
             * `(int) $e->getCode()` no vale para cualquier excepcion: un QueryException trae
             * el SQLSTATE como cadena —'HY000', '23000'— y castearlo da 0, que no es un
             * estado HTTP valido. Symfony reventaba con «The HTTP status code "0" is not
             * valid» y el error real quedaba enterrado bajo un 500 sin mensaje.
             */
            $codigo = (int) $e->getCode();

            return ApiResponse::error(
                message: $e->getMessage(),
                code: ($codigo >= 400 && $codigo < 600) ? $codigo : 500
            );
        }
    }
}
