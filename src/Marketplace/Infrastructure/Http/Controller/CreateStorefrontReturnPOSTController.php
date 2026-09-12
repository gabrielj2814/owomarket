<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\CreateCustomerReturnRequestUseCase;
use Src\Marketplace\Infrastructure\Http\Support\ResolvesStorefrontCustomer;
use Src\Shared\Helper\ApiResponse;

/**
 * El comprador de una tienda abre una reclamación (subsistema 5, fase 2 del escaparate).
 *
 * Es la última pieza que le faltaba a ese comprador: desde la fase 1 ya veía sus pedidos y
 * confirmaba la entrega, y ahí se le acababa el camino. Podía dar por recibido un producto roto
 * y no tenía dónde decirlo.
 *
 * **Puerta nueva, mismo caso de uso.** Lo único que cambia respecto al portal central es quién
 * encuentra el pedido —un `ClaimableOrderLocator` que lee las tablas del inquilino— y de dónde
 * sale la identidad del comprador. Todo lo demás —las reglas, la fila que se guarda, y después
 * revertir la comisión, calcular la cobertura y mover la reputación— es exactamente el mismo
 * código, que es como tiene que ser cuando el resultado mueve dinero.
 *
 * La identidad sale de la SESIÓN que dejó el SSO, nunca del cuerpo de la petición: aceptar un
 * `customer_id` enviado por el navegador es el hallazgo A3 de este repositorio, que ya permitió
 * una vez registrar una devolución sobre el pedido de otro.
 */
final class CreateStorefrontReturnPOSTController
{
    use ResolvesStorefrontCustomer;

    public function __construct(
        private readonly CreateCustomerReturnRequestUseCase $crearReclamacion
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $customerId = $this->currentStorefrontCustomerId();

        if ($customerId === '') {
            return ApiResponse::error('Inicia sesión para abrir una reclamación.', 401);
        }

        $validado = $request->validate([
            'order_id' => ['required', 'string'],
            'product_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
        ]);

        try {
            $reclamacion = $this->crearReclamacion->execute($customerId, $validado);

            return ApiResponse::success(
                data: [
                    'id' => $reclamacion->id,
                    'status' => $reclamacion->status,
                    'product_id' => $reclamacion->product_id,
                ],
                message: 'Tu reclamación ha sido registrada. La tienda tiene un plazo para responder.'
            );
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 400;

            return ApiResponse::error($e->getMessage(), $code >= 400 && $code < 600 ? $code : 400);
        }
    }
}
