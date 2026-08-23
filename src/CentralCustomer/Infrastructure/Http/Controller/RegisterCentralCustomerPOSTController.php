<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Src\CentralCustomer\Application\UseCases\RegisterCentralCustomerUseCase;
use Src\Shared\Helper\ApiResponse;

final class RegisterCentralCustomerPOSTController
{
    public function __construct(
        private readonly RegisterCentralCustomerUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            // A4: la definicion vive en AppServiceProvider, no aqui. Antes era min:6
            // mientras el reset pedia min:8.
            'password' => ['required', 'string', Password::defaults()],
            'phone' => 'nullable|string|max:50',
            'document_id' => 'nullable|string|max:50',
        ]);

        try {
            $result = $this->useCase->execute($request->all());

            return ApiResponse::success(
                data: $result,
                message: 'Cuenta creada exitosamente en OwOMarket',
                code: 201
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 422)
            );
        }
    }
}
