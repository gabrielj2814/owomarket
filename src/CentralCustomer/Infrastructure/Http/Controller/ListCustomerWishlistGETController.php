<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\ListCustomerWishlistUseCase;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;

final class ListCustomerWishlistGETController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly ListCustomerWishlistUseCase $listWishlistUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $customerId = $this->currentCustomerId();

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
