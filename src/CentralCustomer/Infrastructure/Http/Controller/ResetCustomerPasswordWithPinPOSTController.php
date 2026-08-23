<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Src\CentralCustomer\Application\UseCases\ResetCentralCustomerPasswordWithPinUseCase;

final class ResetCustomerPasswordWithPinPOSTController
{
    public function __construct(
        private readonly ResetCentralCustomerPasswordWithPinUseCase $resetPasswordUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'pin_code' => ['required', 'string', 'min:4', 'max:10'],
            // A4: misma definicion que el registro, por construccion.
            'password' => ['required', 'string', Password::defaults()],
        ]);

        try {
            $result = $this->resetPasswordUseCase->execute(
                $validated['email'],
                $validated['pin_code'],
                $validated['password']
            );

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => $result['message'],
            ]);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 422;
            $status = $code >= 400 && $code < 600 ? $code : 422;

            return response()->json([
                'code' => $status,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
