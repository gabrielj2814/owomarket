<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\ValidateAndConsumeSsoTokenUseCase;
use Src\Shared\Helper\ApiResponse;

final class ConsumeSsoTokenPOSTController
{
    public function __construct(
        private readonly ValidateAndConsumeSsoTokenUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $result = $this->useCase->execute(
                (string) $request->input('token'),
                $request->getHost()
            );

            // Set session for tenant customer
            session(['tenant_customer_id' => $result['customer']->id]);
            session(['central_customer_id' => $result['central_customer']['id']]);
            session(['customer_name' => $result['central_customer']['name']]);
            session(['customer_email' => $result['central_customer']['email']]);

            return ApiResponse::success(
                data: $result,
                message: 'Sesión iniciada con éxito en la tienda',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 401)
            );
        }
    }
}
