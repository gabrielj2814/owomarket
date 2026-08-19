<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\AuthenticateCentralCustomerUseCase;
use Src\Shared\Helper\ApiResponse;

final class LoginCentralCustomerPOSTController
{
    public function __construct(
        private readonly AuthenticateCentralCustomerUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $result = $this->useCase->execute(
                (string) $request->input('email'),
                (string) $request->input('password')
            );

            return ApiResponse::success(
                data: $result,
                message: 'Sesión iniciada correctamente en OwOMarket',
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
