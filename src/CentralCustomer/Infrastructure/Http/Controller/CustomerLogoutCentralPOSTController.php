<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Src\Shared\Helper\ApiResponse;

/**
 * Cierra la sesión del comprador en el dominio CENTRAL.
 *
 * No existía hasta la Fase 0.3-D: el frontend (CustomerAuthContext.tsx)
 * sólo llamaba a un logout cuando estaba en un dominio de tenant
 * (CustomerLogoutPOSTController, que limpia session('central_customer_id')
 * del lado del tenant) y en el dominio central se limitaba a borrar
 * localStorage — no había ninguna sesión de servidor que cerrar porque el
 * login central nunca la creaba (ver LoginCentralCustomerPOSTController).
 */
final class CustomerLogoutCentralPOSTController
{
    public function __invoke(Request $request): JsonResponse
    {
        Auth::guard('central_customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(data: null, message: 'Sesión cerrada exitosamente', code: 200);
    }
}
