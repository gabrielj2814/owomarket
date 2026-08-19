<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\ListCustomerReturnRequestsUseCase;

final class ListCustomerReturnsGETController
{
    public function __construct(
        private readonly ListCustomerReturnRequestsUseCase $listReturnsUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $customerId = (string) $request->input('customer_id', $request->header('X-Customer-Id', ''));
        $email = $request->input('email');

        if (empty($customerId) && empty($email)) {
            return response()->json([
                'code' => 400,
                'status' => 'error',
                'message' => 'Se requiere el customer_id o email para consultar devoluciones.',
            ], 400);
        }

        try {
            $returns = $this->listReturnsUseCase->execute($customerId, $email ? (string) $email : null);

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
