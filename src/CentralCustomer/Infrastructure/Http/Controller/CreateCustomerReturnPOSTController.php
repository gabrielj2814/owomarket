<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\CreateCustomerReturnRequestUseCase;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;

final class CreateCustomerReturnPOSTController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly CreateCustomerReturnRequestUseCase $createReturnUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string'],
            'product_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
        ]);

        // Antes 'customer_id' salía del body: cualquiera podía registrar una
        // devolución sobre el pedido de otro comprador (hallazgo A3).
        $customerId = $this->currentCustomerId();

        try {
            $returnRequest = $this->createReturnUseCase->execute($customerId, $validated);

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Tu solicitud de devolución ha sido registrada y está en revisión.',
                'data' => [
                    'return_request' => $returnRequest,
                ],
            ]);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 400;
            $status = $code >= 400 && $code < 600 ? $code : 400;

            return response()->json([
                'code' => $status,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
