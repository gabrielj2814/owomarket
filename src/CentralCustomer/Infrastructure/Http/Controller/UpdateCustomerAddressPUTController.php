<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\UpdateCentralCustomerAddressUseCase;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;

final class UpdateCustomerAddressPUTController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly UpdateCentralCustomerAddressUseCase $updateAddressUseCase
    ) {}

    public function __invoke(Request $request, string $id, string $address_id): JsonResponse
    {
        if ($denied = $this->denyIfNotOwnProfile($id)) {
            return $denied;
        }

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:10'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        try {
            $address = $this->updateAddressUseCase->execute($id, $address_id, $validated);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Dirección actualizada correctamente.',
                'data' => [
                    'address' => $address,
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
