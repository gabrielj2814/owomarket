<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que el identificador de usuario que viaja en la URL sea el de quien pide
 * (hallazgos P1 y P2 de la auditoría de paneles).
 *
 * Alias registrado en bootstrap/app.php: 'own_user'
 *
 * Uso:
 *   Route::get('/owner/backoffice/{user_uuid}/wallet', ...)->middleware(['auth', 'own_user']);
 *
 * **El problema que cierra.** Varias pantallas reciben el uuid del usuario como segmento
 * de la URL y se lo pasan al caso de uso tal cual, sin compararlo nunca con la sesión.
 * Cambiar ese uuid en la barra de direcciones bastaba para leer la billetera de otro
 * propietario —ventas, comisiones, saldo y referencias de pago— o para leer y modificar
 * el perfil de otro administrador.
 *
 * **Por qué un middleware y no un `if` en cada controlador.** Son siete rutas repartidas
 * en dos módulos, y el patrón se repetirá en cuanto se añada la octava. La corrección de
 * los hallazgos A2, A3 y A7 se hizo controlador a controlador y por eso alcanzó a unos y
 * se saltó a otros: `change-password` quedó protegido y sus tres hermanas del mismo bloque
 * no. Una regla declarada en la ruta se ve al leerla, y se ve también cuando falta.
 *
 * **Nadie pasa por encima, ni el superadministrador.** Estas rutas son «lo mío»: el
 * expediente 360 existe justamente para que la administración consulte una tienda ajena
 * por la puerta que sí registra quién miró.
 */
final class EnsureRouteUserIsSelf
{
    /**
     * @param  string  $parametro  Nombre del segmento de ruta que lleva el uuid.
     */
    public function handle(Request $request, Closure $next, string $parametro = 'user_uuid'): Response
    {
        $user = $request->user();

        if ($user === null) {
            // Igual que el resto de alias de autorización: si alguien lo coloca sin 'auth'
            // delante, responde 401 en vez de dejar pasar la petición.
            return $this->denegar($request, 'Debes iniciar sesión para acceder a este recurso.', 401);
        }

        $enLaUrl = (string) $request->route($parametro);

        if ($enLaUrl === '' || $enLaUrl !== (string) $user->getAuthIdentifier()) {
            return $this->denegar($request, 'No puedes acceder a los datos de otro usuario.', 403);
        }

        return $next($request);
    }

    private function denegar(Request $request, string $mensaje, int $codigo): Response
    {
        if ($request->expectsJson()) {
            return ApiResponse::error(message: $mensaje, code: $codigo);
        }

        abort($codigo, $mensaje);
    }
}
