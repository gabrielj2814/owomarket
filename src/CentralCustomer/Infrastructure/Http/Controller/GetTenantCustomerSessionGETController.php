<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as TenantCustomer;
use Src\Shared\Helper\ApiResponse;

final class GetTenantCustomerSessionGETController
{
    public function __invoke(Request $request): JsonResponse
    {
        $tenantCustomerId = session('tenant_customer_id');

        if (! $tenantCustomerId) {
            return ApiResponse::success(
                data: [
                    'authenticated' => false,
                    'customer' => null,
                ],
                message: 'No hay sesión de cliente activa'
            );
        }

        $customer = TenantCustomer::find($tenantCustomerId);

        if (! $customer) {
            session()->forget(['tenant_customer_id', 'central_customer_id', 'customer_name', 'customer_email']);

            return ApiResponse::success(
                data: [
                    'authenticated' => false,
                    'customer' => null,
                ],
                message: 'Sesión expirada'
            );
        }

        return ApiResponse::success(
            data: [
                'authenticated' => true,
                'customer' => $customer,
                'central_customer_id' => session('central_customer_id'),
            ],
            message: 'Sesión de cliente activa'
        );
    }
}
