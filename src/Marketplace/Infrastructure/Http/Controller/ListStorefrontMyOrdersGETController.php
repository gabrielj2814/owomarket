<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\Marketplace\Application\UseCase\ListStorefrontCustomerOrdersUseCase;
use Src\Marketplace\Infrastructure\Http\Support\ResolvesStorefrontCustomer;
use Src\Shared\Helper\ApiResponse;

/**
 * Los pedidos del comprador en esta tienda (fase 1 del plan del escaparate).
 *
 * Sin sesion devuelve 401 y no una lista vacia: son cosas distintas --«no has entrado» frente a
 * «no has comprado nada»-- y la pantalla tiene que poder decir cual de las dos es.
 */
final class ListStorefrontMyOrdersGETController
{
    use ResolvesStorefrontCustomer;

    public function __construct(
        private readonly ListStorefrontCustomerOrdersUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $customerId = $this->currentStorefrontCustomerId();

        if ($customerId === '') {
            return ApiResponse::error('Inicia sesión para ver tus pedidos.', 401);
        }

        /*
         * La cedula, en `meta` y no dentro de cada pedido: es una condicion del COMPRADOR, no
         * de la compra. Repetirla por pedido invitaria a tratarla como si dependiera de cual.
         *
         * Va en la misma respuesta y no en una peticion aparte porque la pantalla la necesita
         * **antes** de abrir el formulario: sin cedula el backend devuelve 422 al enviar, y
         * descubrirlo entonces es perder la explicacion ya escrita.
         */
        $cedula = (string) (CentralCustomer::where('id', $customerId)->value('document_id') ?? '');

        return ApiResponse::success(
            data: $this->useCase->execute($customerId),
            message: 'Pedidos recuperados correctamente.',
            meta: ['has_document_id' => trim($cedula) !== '']
        );
    }
}
