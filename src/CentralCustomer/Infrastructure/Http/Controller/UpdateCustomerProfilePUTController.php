<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\UpdateCentralCustomerProfileUseCase;

final class UpdateCustomerProfilePUTController
{
    public function __construct(
        private readonly UpdateCentralCustomerProfileUseCase $updateProfileUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'document_id' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'string'],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:8'],
        ]);

        try {
            $customer = $this->updateProfileUseCase->execute($id, $validated);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Perfil actualizado correctamente.',
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'document_id' => $customer->document_id,
                        'avatar' => $customer->avatar,
                        'addresses' => $customer->addresses,
                    ],
                ],
            ]);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;

            return response()->json([
                'code' => $status,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
