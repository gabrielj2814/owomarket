<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Throwable;

/**
 * El comerciante declara que el pedido llego. **Esto ya no libera el dinero: arranca el
 * reloj.**
 *
 * Ocupa el sitio que tenia `ReleaseOrderCommissionUseCase` en `DeliverOrderUseCase` y en
 * `MarkShipmentAsDeliveredUseCase`, que son los dos caminos por los que un pedido llega a
 * `delivered`. Los dos estan expuestos en `routes/tenantApi.php`, es decir: **el comerciante
 * declaraba su propia entrega y con eso liberaba su propio dinero.** Esa era la puerta.
 *
 * A partir de aqui la liberacion tiene dos causas legitimas y ninguna es el comerciante:
 * que el comprador confirme (`ConfirmOrderDeliveryUseCase`) o que pase el plazo sin que
 * nadie diga nada (`ReleaseUnconfirmedDeliveriesUseCase`).
 *
 * Sin `final`: los tests la sustituyen por un doble de Mockery, como el caso de uso al que
 * releva (ver `reglas.md`).
 */
class DeclareOrderDeliveredUseCase
{
    /**
     * @param  string  $orderId  ID del pedido DE LA TIENDA, igual que en
     *                           `PlatformCommission.order_id`.
     * @return bool Si quedo registrada la declaracion.
     */
    public function execute(string $orderId): bool
    {
        try {
            // El enlace tienda-pedido-pedido central ya existe en la comision, que es
            // central y se escribe al despachar. Releerlo de ahi evita tener que averiguar
            // en que contexto de inquilino estamos.
            $commission = PlatformCommission::where('order_id', $orderId)->first();

            if ($commission === null) {
                // Un pedido sin comision no genera dinero que liberar, asi que no hay
                // expediente que abrir. Pasa con los pedidos anteriores a la monetizacion.
                return false;
            }

            $existente = OrderDeliveryConfirmation::where('tenant_id', $commission->tenant_id)
                ->where('order_id', $orderId)
                ->first();

            if ($existente !== null) {
                // Declarar dos veces no mueve la fecha de la primera: el reloj del plazo se
                // cuenta desde que se dijo por primera vez, o reenviar la declaracion seria
                // una forma de aplazar indefinidamente la liberacion automatica.
                if ($existente->declared_delivered_at === null) {
                    $existente->declared_delivered_at = now();
                    $existente->save();
                }

                return true;
            }

            OrderDeliveryConfirmation::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $commission->tenant_id,
                'order_id' => $orderId,
                'central_order_id' => $commission->central_order_id,
                'customer_id' => $this->compradorDe($commission->central_order_id, $orderId),
                'declared_delivered_at' => now(),
            ]);

            return true;
        } catch (Throwable $e) {
            // Misma politica que la liberacion a la que releva: un fallo en la base central
            // no puede tumbar la entrega del pedido, pero tiene que dejar rastro. Aqui lo
            // que se pierde es el reloj, y sin reloj el dinero se queda retenido.
            Log::error('No se pudo registrar la entrega declarada por el comerciante.', [
                'order_id' => $orderId,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * El comprador central al que pertenece este pedido.
     *
     * Es lo que despues autoriza la confirmacion, asi que no es un dato informativo: si sale
     * mal, o no se puede confirmar o lo confirma quien no debe.
     *
     * Dos caminos porque hay dos formas de comprar. En el marketplace el pedido central ya
     * dice de quien es. En el escaparate no hay pedido central, asi que se pasa por el
     * cliente de la tienda y su `central_uuid` --el enlace que dejo el SSO--. Un comprador
     * de escaparate sin cuenta central se queda sin enlace, y ese pedido solo podra
     * liberarse por plazo.
     */
    private function compradorDe(?string $centralOrderId, string $orderId): ?string
    {
        try {
            if ($centralOrderId !== null) {
                return DB::connection($this->conexionCentral())
                    ->table('central_orders')
                    ->where('id', $centralOrderId)
                    ->value('customer_id');
            }

            // Venta de escaparate: se resuelve en la base del inquilino, que es donde
            // estamos cuando el comerciante declara la entrega.
            $customerId = DB::table('orders')->where('id', $orderId)->value('customer_id');

            if ($customerId === null) {
                return null;
            }

            return DB::table('customers')->where('id', $customerId)->value('central_uuid');
        } catch (Throwable $e) {
            Log::warning('No se pudo resolver el comprador de una entrega declarada.', [
                'order_id' => $orderId,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function conexionCentral(): string
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }
}
