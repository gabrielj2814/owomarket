<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\SetDefaultCentralCustomerAddressUseCase;

final class SetDefaultCustomerAddressPATCHController
{
    public function __construct(
        private readonly SetDefaultCentralCustomerAddressUseCase $setDefaultAddressUseCase
    ) {}

    public function __invoke(Request $request, string $id, string $address_id): JsonResponse
    {
        try {
            $address = $this->setDefaultAddressUseCase->execute($id, $address_id);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Dirección predeterminada actualizada.',
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
