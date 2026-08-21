<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Support;

use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;

/**
 * Resuelve la identidad del comprador central SIEMPRE desde el guard de sesión
 * 'central_customer' — nunca desde 'customer_id', el {id} de la URL o la
 * cabecera X-Customer-Id del request (hallazgo A3).
 *
 * Las rutas que usan este trait ya están protegidas por el middleware
 * 'auth:central_customer', así que currentCustomerId() nunca devuelve cadena
 * vacía dentro de esos controladores.
 */
trait ResolvesAuthenticatedCustomer
{
    private function currentCustomerId(): string
    {
        return (string) auth('central_customer')->id();
    }

    /**
     * Para rutas con un {id} de recurso en la URL (perfil, direcciones):
     * exige que coincida con la sesión. Antes cualquiera podía pasar el UUID
     * de otro comprador ahí y operar sobre su cuenta.
     */
    private function denyIfNotOwnProfile(string $profileId): ?JsonResponse
    {
        if ($profileId !== $this->currentCustomerId()) {
            return ApiResponse::error('No tienes acceso a este recurso.', 403);
        }

        return null;
    }
}
