<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;

final class CustomerLogoutPOSTController
{
    public function __invoke(Request $request): JsonResponse
    {
        session()->forget(['tenant_customer_id', 'central_customer_id', 'customer_name', 'customer_email']);

        return ApiResponse::success(
            data: null,
            message: 'Sesión cerrada correctamente'
        );
    }
}
