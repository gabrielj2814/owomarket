<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\GetCustomerOrderDetailUseCase;

final class GetCustomerOrderDetailGETController
{
    public function __construct(
        private readonly GetCustomerOrderDetailUseCase $getOrderDetailUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $customerId = (string) $request->input('customer_id', $request->header('X-Customer-Id', ''));

        try {
            $order = $this->getOrderDetailUseCase->execute($customerId, $id);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => [
                    'order' => $order,
                ],
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
