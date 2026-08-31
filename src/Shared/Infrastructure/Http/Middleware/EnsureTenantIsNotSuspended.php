<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta el acceso al backoffice de una tienda suspendida (Fase 5 del plan de wallet).
 *
 * **El estado existía y no lo comprobaba nadie.** `TenantStatus::STATUS_SUSPENDED` estaba
 * definido, `TenantRepository::suspended()` lo escribía, dos casos de uso lo invocaban y había
 * endpoints para llamarlos — pero ni una línea del proyecto lo leía para impedir nada. Suspender
 * una tienda era escribir una palabra en una columna: el comerciante entraba igual, vendía
 * igual, y lo único que cambiaba era un contador en el panel de admin.
 *
 * Es el patrón que la auditoría de este proyecto llama «una protección escrita y sin cablear».
 *
 * **Qué se bloquea y qué no.** El backoffice sí; el escaparate no. La tienda sigue vendiendo y
 * la deuda sigue creciendo, pero el comerciante no puede gestionarla hasta ponerse al día. Es
 * la presión que hace cobrable la comisión del canal escaparate, donde el comprador paga
 * directo al comerciante y la plataforma no puede retener nada.
 *
 * Castigar al comprador cerrando el escaparate sería cobrarle la deuda a quien no la debe.
 *
 * **`inactive` no bloquea.** El enum admite `active`, `inactive` y `suspended`, y sólo el
 * último es una sanción. Bloquear también `inactive` metería en el mismo saco a una tienda que
 * simplemente no ha terminado de darse de alta.
 */
final class EnsureTenantIsNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant === null || ($tenant->status ?? null) !== 'suspended') {
            return $next($request);
        }

        $mensaje = 'Tu tienda está suspendida. El escaparate sigue funcionando, pero no puedes '
            .'gestionarla hasta regularizar tu situación con la plataforma.';

        // El comerciante tiene que poder ver POR QUÉ. Un 403 mudo delante de cada pantalla
        // manda a soporte a preguntar qué pasa, que es trabajo para todos.
        if ($request->expectsJson()) {
            return ApiResponse::error(message: $mensaje, code: 403);
        }

        abort(403, $mensaje);
    }
}
