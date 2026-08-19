<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\SendCentralCustomerPasswordResetPinUseCase;

final class SendCustomerPasswordResetPinPOSTController
{
    public function __construct(
        private readonly SendCentralCustomerPasswordResetPinUseCase $sendPinUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $result = $this->sendPinUseCase->execute($validated['email']);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'email' => $result['email'],
                    'expires_at' => $result['expires_at'],
                    'pin_code' => app()->environment('local', 'testing') ? $result['pin_code'] : null,
                ],
            ]);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 400;
            $status = $code >= 400 && $code < 600 ? $code : 400;

            return response()->json([
                'code' => $status,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
