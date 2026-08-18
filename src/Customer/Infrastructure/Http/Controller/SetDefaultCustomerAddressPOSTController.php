<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Controller;

use DomainException;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Customer\Application\UseCases\SetDefaultCustomerAddressUseCase;
use Src\Customer\Domain\Exceptions\CustomerAddressNotFoundException;
use Src\Customer\Domain\Exceptions\CustomerNotFoundException;

final class SetDefaultCustomerAddressPOSTController
{
    public function __construct(
        private readonly SetDefaultCustomerAddressUseCase $useCase
    ) {}

    public function __invoke(string $id, string $addressId): JsonResponse
    {
        try {
            $customer = $this->useCase->execute($id, $addressId);

            return response()->json([
                'status' => 'success',
                'message' => 'Dirección predeterminada actualizada.',
                'data' => $customer->toArray(),
            ], 200);
        } catch (CustomerNotFoundException|CustomerAddressNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado al actualizar la dirección predeterminada: '.$e->getMessage(),
            ], 500);
        }
    }
}
