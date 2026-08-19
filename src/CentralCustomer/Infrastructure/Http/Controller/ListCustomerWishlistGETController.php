<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\ListCustomerWishlistUseCase;

final class ListCustomerWishlistGETController
{
    public function __construct(
        private readonly ListCustomerWishlistUseCase $listWishlistUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $customerId = (string) $request->input('customer_id', $request->header('X-Customer-Id', ''));

        if (empty($customerId)) {
            return response()->json([
                'code' => 400,
                'status' => 'error',
                'message' => 'Se requiere el customer_id para consultar la lista de deseos.',
            ], 400);
        }

        try {
            $wishlist = $this->listWishlistUseCase->execute($customerId);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $wishlist,
            ]);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 400;
            $status = $code >= 400 && $code < 600 ? $code : 400;

            return response()->json([
                'code' => $status,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
