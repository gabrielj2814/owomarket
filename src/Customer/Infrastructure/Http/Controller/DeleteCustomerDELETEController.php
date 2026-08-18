<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Customer\Application\UseCases\DeleteCustomerUseCase;
use Src\Customer\Domain\Exceptions\CustomerNotFoundException;

final class DeleteCustomerDELETEController
{
    public function __construct(
        private readonly DeleteCustomerUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Cliente eliminado exitosamente.',
            ], 200);
        } catch (CustomerNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado al eliminar el cliente: '.$e->getMessage(),
            ], 500);
        }
    }
}
