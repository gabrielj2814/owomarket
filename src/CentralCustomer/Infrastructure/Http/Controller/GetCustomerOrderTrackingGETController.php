<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\GetCustomerOrderTrackingUseCase;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;

final class GetCustomerOrderTrackingGETController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly GetCustomerOrderTrackingUseCase $getTrackingUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $customerId = $this->currentCustomerId();

        try {
            $tracking = $this->getTrackingUseCase->execute($customerId, $id);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $tracking,
            ]);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 404;
            $status = $code >= 400 && $code < 600 ? $code : 404;

            return response()->json([
                'code' => $status,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
