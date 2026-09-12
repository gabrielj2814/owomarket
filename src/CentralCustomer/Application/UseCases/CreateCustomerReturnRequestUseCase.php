<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\Contracts\ClaimableOrderLocator;
use Src\CentralCustomer\Application\DTOs\ClaimableOrderData;
use Src\CentralCustomer\Application\Service\ClaimWindow;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;

/**
 * Abrir una reclamacion (subsistema 5).
 *
 * ## Ya no sabe donde vive el pedido
 *
 * Hasta la fase 2 del escaparate este caso de uso buscaba el pedido en `CentralOrder`, y eso
 * dejaba fuera al comprador de una tienda: su pedido vive en la base del inquilino. Ahora el
 * pedido entra por `ClaimableOrderLocator` --uno para cada origen-- y aqui solo quedan **las
 * reglas**, que son las mismas para los dos y se pueden leer de un tiron.
 *
 * Lo que NO cambia: la reclamacion se guarda en la tabla CENTRAL, siempre. El administrador,
 * la reputacion de la tienda y el fondo de cobertura viven en la base central, y una
 * reclamacion guardada en la del inquilino seria invisible para los tres.
 *
 * ## Las dos reglas nuevas, y por que no estaban
 *
 * La pantalla del portal solo ofrecia pedidos `completed`, pero **ese filtro vivia solo en el
 * navegador**: contra la API se podia abrir una reclamacion sobre un pedido recien creado y
 * sin pagar. Al escribir la segunda puerta habia que elegir entre replicar el agujero o
 * cerrarlo, y se cerro:
 *
 * 1. **Entregado.** Un `OrderDeliveryConfirmation` solo nace en `DeclareOrderDeliveredUseCase`,
 *    asi que su mera existencia ya significa que la tienda declaro la entrega. No hizo falta
 *    inventar ningun estado nuevo.
 * 2. **Dentro de plazo.** `ClaimWindow`, 60 dias configurables desde la entrega. Reclamar mas
 *    tarde es reclamar contra dinero que la plataforma ya solto entero.
 *
 * Es un cambio de comportamiento tambien para el portal central. Es deliberado.
 */
final class CreateCustomerReturnRequestUseCase
{
    public function __construct(
        private readonly ClaimableOrderLocator $localizador,
        private readonly ClaimWindow $ventana
    ) {}

    /**
     * @param  array{order_id: string, product_id: string, reason: string, description: string, photos?: array<int, string>|null}  $data
     */
    public function execute(string $customerId, array $data): CustomerReturnRequest
    {
        $pedido = $this->localizador->find($data['order_id'], $data['product_id'], $customerId);

        if ($pedido === null) {
            // Un solo mensaje para «no existe», «no es tuyo» y «ese producto no esta en el
            // pedido»: distinguirlos le contaria a quien prueba identificadores ajenos cual
            // de sus intentos acerto.
            throw new Exception('El pedido no fue encontrado o no pertenece a tu cuenta.', 404);
        }

        $this->exigirCedula($customerId);

        $entregadoEl = $this->entregadoEl($pedido->tenantOrderId);

        if ($entregadoEl === null) {
            throw new Exception(
                'Todavía no puedes reclamar este pedido: la tienda no ha marcado la entrega.',
                422
            );
        }

        if (! $this->ventana->isOpenFor($entregadoEl)) {
            throw new Exception(
                'El plazo para reclamar este pedido venció ('.$this->ventana->days().' días desde la entrega).',
                422
            );
        }

        $this->exigirQueNoHayaOtraViva($pedido);

        return CustomerReturnRequest::create([
            'id' => (string) Str::uuid(),
            'order_id' => $pedido->orderId,
            /*
             * A que base apunta `order_id`. Sin esto los dos origenes son indistinguibles
             * --ambos son cadenas con forma de UUID-- y el portal enlazaria la reclamacion de
             * una tienda a un pedido central que no existe.
             */
            'order_source' => $pedido->orderSource,
            'order_number' => $pedido->orderNumber,
            /*
             * Subsistema 5: la columna que conecta la reclamacion con el dinero.
             *
             * La comision vive por pedido DE TIENDA (`platform_commissions.order_id`). Sin
             * capturarlo aqui, aprobar la reclamacion no sabria que comision revertir, o peor,
             * revertiria la de otra tienda del mismo carrito.
             */
            'tenant_order_id' => $pedido->tenantOrderId,
            // Medicion: lo que de verdad se reclama. Sin importe no se puede dimensionar nada
            // --ni el fondo, ni el tope de cobertura, ni el techo mensual de alarma--.
            'amount' => $pedido->amount,
            'delivered_at' => $entregadoEl->toDateTimeString(),
            'customer_id' => $customerId,
            'customer_email' => $pedido->customerEmail,
            'product_id' => $pedido->productId,
            'product_name' => $pedido->productName,
            'tenant_id' => $pedido->tenantId,
            'reason' => trim($data['reason']),
            'description' => trim($data['description']),
            'photos' => $data['photos'] ?? [],
            'status' => 'requested',
        ]);
    }

    /**
     * Subsistema 1, fase D: KYC del comprador, exigido AL RECLAMAR.
     *
     * No al comprar -- pedir cedula para una compra mata la conversion, y la decision lo dice:
     * «datos minimos para comprar, KYC completo para abrir una reclamacion». Aqui si: una
     * reclamacion puede acabar moviendo dinero y, en el peor caso, en una denuncia, y ninguna
     * de las dos cosas funciona contra alguien sin identificar.
     *
     * Se lee de la cuenta central del comprador y no de la relacion del pedido: es la misma
     * comprobacion para los dos origenes y no depende de como se creo la compra.
     */
    private function exigirCedula(string $customerId): void
    {
        $cedula = (string) (CentralCustomer::where('id', $customerId)->value('document_id') ?? '');

        if (trim($cedula) === '') {
            throw new Exception(
                'Antes de abrir una reclamación necesitamos tu cédula. Complétala en tu perfil.',
                422
            );
        }
    }

    /**
     * Una reclamacion viva por producto y pedido.
     *
     * Se compara tambien `order_source` porque desde la fase 2 dos origenes distintos pueden
     * traer identificadores de tablas distintas.
     */
    private function exigirQueNoHayaOtraViva(ClaimableOrderData $pedido): void
    {
        $existe = CustomerReturnRequest::where('order_id', $pedido->orderId)
            ->where('order_source', $pedido->orderSource)
            ->where('product_id', $pedido->productId)
            ->whereIn('status', CustomerReturnRequest::BLOQUEAN_NUEVA)
            ->exists();

        if ($existe) {
            throw new Exception('Ya existe una solicitud de devolución activa para este producto.', 422);
        }
    }

    /**
     * Cuando se dio por recibido el pedido. `null` significa que la tienda **no** ha declarado
     * la entrega, porque el expediente solo lo crea `DeclareOrderDeliveredUseCase`.
     *
     * Sirve para dos cosas a la vez: la regla de arriba, y la medicion de **cuantos dias
     * despues de recibir** llega una reclamacion --que es lo unico que dira si los 60 dias de
     * retencion del fondo son los correctos--.
     */
    private function entregadoEl(string $tenantOrderId): ?Carbon
    {
        if (trim($tenantOrderId) === '') {
            return null;
        }

        $expediente = OrderDeliveryConfirmation::where('order_id', $tenantOrderId)->first();

        if ($expediente === null) {
            return null;
        }

        return $expediente->released_at
            ?? $expediente->confirmed_at
            ?? $expediente->declared_delivered_at;
    }
}
