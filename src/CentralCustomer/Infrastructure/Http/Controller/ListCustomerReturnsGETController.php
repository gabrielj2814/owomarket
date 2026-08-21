<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\ListCustomerReturnRequestsUseCase;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;

final class ListCustomerReturnsGETController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly ListCustomerReturnRequestsUseCase $listReturnsUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $customerId = $this->currentCustomerId();

        try {
            $returns = $this->listReturnsUseCase->execute($customerId, null);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $returns,
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
