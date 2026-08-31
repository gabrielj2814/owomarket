<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ConfirmStorefrontPaymentUseCase;
use Src\Shared\Helper\ApiResponse;

final class ConfirmStorefrontPaymentPOSTController
{
    public function __construct(
        private readonly ConfirmStorefrontPaymentUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $user = auth()->user();

            $resultado = $this->useCase->execute(
                $id,
                (string) ($user?->id ?? 'system_admin'),
                $request->input('reference'),
                $request->input('notes')
            );

            return ApiResponse::success(
                data: $resultado,
                message: 'Cobro confirmado. El importe entra en la billetera de la tienda.'
            );
        } catch (Exception $e) {
            // Acotado a un rango HTTP valido: un `getCode()` que no lo sea revienta Symfony
            // con "status code 0 is not valid" y entierra el mensaje real (hallazgo PR2).
            $code = (int) $e->getCode();

            return ApiResponse::error(
                message: $e->getMessage(),
                code: ($code >= 400 && $code <= 599) ? $code : 400
            );
        }
    }
}
