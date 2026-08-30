<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ConfirmCentralOrderPaymentUseCase;
use Src\Shared\Helper\ApiResponse;

final class ConfirmAdminOrderPaymentPOSTController
{
    public function __construct(
        private readonly ConfirmCentralOrderPaymentUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $user = auth()->user();
            $adminUserId = (string) ($user?->id ?? 'system_admin');

            $result = $this->useCase->execute(
                $id,
                $adminUserId,
                $request->input('reference'),
                $request->input('notes')
            );

            return ApiResponse::success(
                data: $result,
                message: 'Cobro confirmado. Las comisiones de esta orden ya entran en liquidación.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                // Acotado a un rango HTTP valido: un `getCode()` que no lo sea revienta
                // Symfony con "status code 0 is not valid" y entierra el mensaje real,
                // que es lo que costo tres diagnosticos en PR2.
                code: $this->httpCode($e)
            );
        }
    }

    private function httpCode(Exception $e): int
    {
        $code = (int) $e->getCode();

        return ($code >= 400 && $code <= 599) ? $code : 400;
    }
}
