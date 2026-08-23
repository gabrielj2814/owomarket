<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\CentralCustomer\Application\UseCases\ListCustomerAvailableCouponsUseCase;

final class ListCustomerCouponsGETController
{
    public function __construct(
        private readonly ListCustomerAvailableCouponsUseCase $listCouponsUseCase
    ) {}

    public function __invoke(): JsonResponse
    {
        // Hallazgo C1: aqui se leia 'customer_id' del cuerpo o la cabecera X-Customer-Id
        // —las dos fuentes que ResolvesAuthenticatedCustomer documenta como prohibidas— y
        // el valor no se usaba en ninguna parte. Se lee como un agujero de autorizacion sin
        // serlo, asi que fuera: el catalogo es publico y no depende del comprador.
        $coupons = $this->listCouponsUseCase->execute();

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $coupons,
        ]);
    }
}
