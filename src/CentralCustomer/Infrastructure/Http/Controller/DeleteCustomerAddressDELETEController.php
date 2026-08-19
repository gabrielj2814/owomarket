<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\DeleteCentralCustomerAddressUseCase;

final class DeleteCustomerAddressDELETEController
{
    public function __construct(
        private readonly DeleteCentralCustomerAddressUseCase $deleteAddressUseCase
    ) {}

    public function __invoke(Request $request, string $id, string $address_id): JsonResponse
    {
        try {
            $result = $this->deleteAddressUseCase->execute($id, $address_id);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => $result['message'],
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
