<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Controller;

use DomainException;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Customer\Application\UseCases\CreateCustomerUseCase;
use Src\Customer\Infrastructure\Http\Request\CreateCustomerFormRequest;

final class CreateCustomerPOSTController
{
    public function __construct(
        private readonly CreateCustomerUseCase $useCase
    ) {}

    public function __invoke(CreateCustomerFormRequest $request): JsonResponse
    {
        try {
            $customer = $this->useCase->execute($request->toDto());

            return response()->json([
                'status' => 'success',
                'message' => 'Cliente registrado exitosamente.',
                'data' => $customer->toArray(),
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado al registrar el cliente: '.$e->getMessage(),
            ], 500);
        }
    }
}
