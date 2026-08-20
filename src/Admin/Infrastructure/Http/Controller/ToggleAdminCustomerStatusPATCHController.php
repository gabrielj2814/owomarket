<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ToggleCentralCustomerStatusUseCase;
use Src\Shared\Helper\ApiResponse;

final class ToggleAdminCustomerStatusPATCHController
{
    public function __construct(
        private readonly ToggleCentralCustomerStatusUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $customer = $this->useCase->execute($id, $request->input('reason'));

            $statusText = $customer->is_active ? 'activada' : 'bloqueada';

            return ApiResponse::success(
                data: $customer,
                message: "Cuenta del cliente {$statusText} exitosamente."
            );
        } catch (Exception $e) {
            $code = (int) $e->getCode();
            $httpCode = ($code >= 100 && $code <= 599) ? $code : 400;

            return ApiResponse::error(
                message: $e->getMessage(),
                code: $httpCode
            );
        }
    }
}
