<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\ListCustomerOrdersUseCase;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;
use Src\Shared\Helper\ApiResponse;

final class ListCustomerOrdersGETController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly ListCustomerOrdersUseCase $listOrdersUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        // Antes 'customer_id' salía de la query o de la cabecera X-Customer-Id:
        // cualquiera podía leer el historial de pedidos de otro comprador con
        // solo conocer su UUID (hallazgo A3). La ruta ya exige sesión.
        $customerId = $this->currentCustomerId();
        $email = null;

        try {
            $filters = [
                'status' => $request->input('status'),
                'search' => $request->input('search'),
                'limit' => $request->input('limit') ? (int) $request->input('limit') : 10,
                'page' => $request->input('page') ? (int) $request->input('page') : 1,
            ];

            $result = $this->listOrdersUseCase->execute($customerId, $email ? (string) $email : null, $filters);

            // Hallazgo N37: esto devolvia la paginacion en `meta` y sin `per_page`, una de
            // las seis formas que convivian. Ahora usa el unico formato de la API.
            return ApiResponse::paginated(
                data: $result['data'],
                total: $result['total'],
                currentPage: $result['current_page'],
                perPage: $result['per_page'] ?? count($result['data']),
                lastPage: $result['last_page'],
                message: 'Pedidos consultados exitosamente'
            );
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
