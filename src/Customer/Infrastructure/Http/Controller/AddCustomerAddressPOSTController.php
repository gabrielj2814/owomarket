<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Controller;

use DomainException;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Customer\Application\UseCases\AddCustomerAddressUseCase;
use Src\Customer\Domain\Exceptions\CustomerNotFoundException;
use Src\Customer\Infrastructure\Http\Request\AddCustomerAddressFormRequest;

final class AddCustomerAddressPOSTController
{
    public function __construct(
        private readonly AddCustomerAddressUseCase $useCase
    ) {}

    public function __invoke(string $id, AddCustomerAddressFormRequest $request): JsonResponse
    {
        try {
            $customer = $this->useCase->execute($id, $request->toDto());

            return response()->json([
                'status' => 'success',
                'message' => 'Dirección agregada exitosamente.',
                'data' => $customer->toArray(),
            ], 201);
        } catch (CustomerNotFoundException $e) {
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
                'message' => 'Ocurrió un error inesperado al agregar la dirección: '.$e->getMessage(),
            ], 500);
        }
    }
}
