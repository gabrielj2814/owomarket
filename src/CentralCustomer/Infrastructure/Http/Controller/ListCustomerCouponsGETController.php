<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\ListCustomerAvailableCouponsUseCase;

final class ListCustomerCouponsGETController
{
    public function __construct(
        private readonly ListCustomerAvailableCouponsUseCase $listCouponsUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $customerId = (string) $request->input('customer_id', $request->header('X-Customer-Id', ''));

        $coupons = $this->listCouponsUseCase->execute($customerId);

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $coupons,
        ]);
    }
}
