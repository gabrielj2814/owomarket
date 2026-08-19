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
            'customer_id' => 'required|string',
            'target_domain' => 'nullable|string',
        ]);

        try {
            $ssoToken = $this->useCase->execute(
                (string) $request->input('customer_id'),
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
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 422)
            );
        }
    }
}
