<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Controller;

use DomainException;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Customer\Application\UseCases\UpdateCustomerUseCase;
use Src\Customer\Domain\Exceptions\CustomerNotFoundException;
use Src\Customer\Infrastructure\Http\Request\UpdateCustomerFormRequest;

final class UpdateCustomerPUTController
{
    public function __construct(
        private readonly UpdateCustomerUseCase $useCase
    ) {}

    public function __invoke(string $id, UpdateCustomerFormRequest $request): JsonResponse
    {
        try {
            $customer = $this->useCase->execute($id, $request->toDto());

            return response()->json([
                'status' => 'success',
                'message' => 'Cliente actualizado exitosamente.',
                'data' => $customer->toArray(),
            ], 200);
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
                'message' => 'Ocurrió un error inesperado al actualizar el cliente: '.$e->getMessage(),
            ], 500);
        }
    }
}
