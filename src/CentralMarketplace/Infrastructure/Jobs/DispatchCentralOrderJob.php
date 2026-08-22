<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Src\CentralMarketplace\Application\UseCases\DispatchCentralOrderToTenantsUseCase;
use Src\CentralMarketplace\Infrastructure\Eloquent\Models\CentralOrderDispatch;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Throwable;

/**
 * Despacha un pedido central a las tiendas participantes, en segundo plano y con
 * reintentos (hallazgo N17).
 *
 * **Por qué en cola.** El despacho escribe en la base de datos de cada tienda, una
 * conexión distinta por tienda. Dentro de la petición del checkout eso significaba que
 * la respuesta al comprador quedaba a merced de la tienda más lenta, y que si una
 * fallaba su fila se quedaba en `failed` sin que nada la volviera a intentar nunca.
 *
 * **Qué NO cambia.** El pedido central y sus líneas se siguen creando de forma síncrona,
 * dentro de su transacción, antes de encolar esto. El comprador recibe su número de
 * pedido igual que antes; lo único que se difiere es la propagación a las tiendas. La
 * pantalla de confirmación lee `central_order_items`, así que se pinta completa aunque
 * el despacho aún no haya corrido — sólo faltará el `tenant_order_id` unos segundos.
 *
 * **Por qué es seguro reintentar.** El despacho es idempotente por (pedido central,
 * tienda) gracias al índice único de `central_order_dispatches` (hallazgo C2). Una tienda
 * ya despachada se salta; sólo se reclaman las que quedaron en `failed`.
 */
final class DispatchCentralOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cinco pasadas, la misma cifra que el tope por tienda de
     * `DispatchCentralOrderToTenantsUseCase::MAX_DISPATCH_ATTEMPTS`.
     */
    public int $tries = 5;

    /**
     * Espera creciente: una tienda caída rara vez vuelve en diez segundos, y machacarla
     * cada diez no la ayuda. La última pasada llega a los ~20 minutos del pedido.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 120, 300, 900];
    }

    public function __construct(private readonly string $centralOrderId) {}

    public function handle(DispatchCentralOrderToTenantsUseCase $useCase): void
    {
        $order = CentralOrder::with(['items', 'customer'])->find($this->centralOrderId);

        if (! $order) {
            // El pedido ya no existe: no hay nada que despachar y reintentar no lo va a
            // resucitar. Se sale sin excepción para no gastar los cinco intentos.
            Log::warning('DispatchCentralOrderJob: el pedido central ya no existe.', [
                'central_order_id' => $this->centralOrderId,
            ]);

            return;
        }

        $useCase->execute($order);

        // El caso de uso no propaga el fallo de una tienda —a propósito: el fallo de una
        // no puede abortar las demás—, así que aquí se mira el resultado en la tabla. Si
        // queda alguna fallida y aún tiene intentos, se lanza para que la cola reintente
        // con espera; el reclamo de `reserveDispatch` hará que la siguiente pasada
        // alcance sólo a las pendientes.
        $fallidas = CentralOrderDispatch::query()
            ->where('central_order_id', $order->id)
            ->where('status', 'failed')
            ->where('attempts', '<', DispatchCentralOrderToTenantsUseCase::MAX_DISPATCH_ATTEMPTS)
            ->pluck('tenant_id')
            ->all();

        if ($fallidas !== []) {
            throw new RuntimeException(
                'Despacho incompleto del pedido central '.$order->order_number.
                '. Tiendas pendientes: '.implode(', ', $fallidas)
            );
        }
    }

    /**
     * Agotados los intentos, esto deja de ser un problema técnico y pasa a ser un pedido
     * cobrado que no llegó a su tienda. Tiene que quedar constancia con nombre y apellido.
     */
    public function failed(Throwable $e): void
    {
        Log::error('Un pedido central no se pudo despachar tras agotar los reintentos.', [
            'central_order_id' => $this->centralOrderId,
            'error' => $e->getMessage(),
        ]);
    }
}
