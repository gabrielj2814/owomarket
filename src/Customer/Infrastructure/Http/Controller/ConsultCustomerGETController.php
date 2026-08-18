<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Customer\Application\UseCases\ConsultCustomerByIdUseCase;
use Src\Customer\Domain\Exceptions\CustomerNotFoundException;

final class ConsultCustomerGETController
{
    public function __construct(
        private readonly ConsultCustomerByIdUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $customer = $this->useCase->execute($id);

            return response()->json([
                'status' => 'success',
                'data' => $customer->toArray(),
            ], 200);
        } catch (CustomerNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado al consultar el cliente: '.$e->getMessage(),
            ], 500);
        }
    }
}
