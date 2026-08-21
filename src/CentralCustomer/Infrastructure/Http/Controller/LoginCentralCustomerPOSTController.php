<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            // Antes este login nunca creaba una sesión real: devolvía un
            // 'token' de 64 caracteres que no se persistía ni se verificaba
            // en ningún lado (código muerto), y cada endpoint del portal
            // confiaba en el customer_id que mandara el cliente (hallazgo A3).
            // Ahora sí autenticamos contra el guard 'central_customer' y
            // regeneramos la sesión para evitar fijación de sesión.
            Auth::guard('central_customer')->login($result['customer']);
            $request->session()->regenerate();

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
