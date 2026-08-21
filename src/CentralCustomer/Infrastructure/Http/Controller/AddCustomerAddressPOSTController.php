<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\AddCentralCustomerAddressUseCase;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;
use Src\Shared\Helper\ApiResponse;

final class AddCustomerAddressPOSTController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly AddCentralCustomerAddressUseCase $useCase
    ) {}

    public function __invoke(string $id, Request $request): JsonResponse
    {
        if ($denied = $this->denyIfNotOwnProfile($id)) {
            return $denied;
        }

        $request->validate([
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'country' => 'nullable|string',
            'label' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        try {
            $address = $this->useCase->execute($id, $request->all());

            return ApiResponse::success(
                data: $address,
                message: 'Dirección registrada exitosamente',
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
