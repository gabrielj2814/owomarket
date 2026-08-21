<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\GenerateCustomerSsoTokenUseCase;
use Src\Shared\Helper\ApiResponse;

final class GenerateSsoTokenPOSTController
{
    public function __construct(
        private readonly GenerateCustomerSsoTokenUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'target_domain' => 'nullable|string',
        ]);

        // Antes 'customer_id' salía del body sin ninguna verificación: cualquiera
        // podía emitir un token SSO válido para suplantar a cualquier comprador
        // (hallazgo A3, el más grave del bloque). Ahora sale del guard de sesión,
        // que exige haber iniciado sesión con la contraseña real.
        $customerId = (string) auth('central_customer')->id();

        try {
            $ssoToken = $this->useCase->execute(
                $customerId,
                $request->input('target_domain') ? (string) $request->input('target_domain') : null
            );

            return ApiResponse::success(
                data: [
                    'token' => $ssoToken->token,
                    'expires_at' => $ssoToken->expires_at->toIso8601String(),
                    'target_domain' => $ssoToken->target_domain,
                ],
                message: 'Token SSO generado exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) && (int) $e->getCode() >= 400 && (int) $e->getCode() < 600
                ? (int) $e->getCode()
                : 422;

            return ApiResponse::error(
                message: $e->getMessage(),
                code: $code
            );
        }
    }
}
