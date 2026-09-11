<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\SupportTicket\Application\Service\UploadSupportAttachmentService;

/**
 * El comerciante deja constancia de que envio el pedido, con fotos o video (subsistema 3,
 * fase B).
 *
 * Esto **no libera nada** --seguir liberando es cosa del comprador o del plazo-- pero cambia
 * la conversacion cuando hay discusion: sin evidencia, «lo envie» contra «no me llego» es la
 * palabra de uno contra la del otro y la plataforma no tiene con que decidir.
 *
 * La evidencia se guarda en el expediente CENTRAL para que la vean el comprador y el
 * administrador: si viviera en la base de la tienda, ninguno de los dos podria consultarla.
 *
 * Reutiliza `UploadSupportAttachmentService`, que ya valida imagenes y video con su limite de
 * tamaño. Escribir un segundo subidor solo habria creado dos sitios donde cambiar el limite.
 */
final class AttachShipmentEvidenceUseCase
{
    public function __construct(
        private readonly UploadSupportAttachmentService $uploader
    ) {}

    /**
     * @param  array<UploadedFile>  $files
     *
     * @throws Exception 404 si el pedido no tiene comision, 409 si ya se libero.
     */
    public function execute(string $orderId, array $files): OrderDeliveryConfirmation
    {
        $commission = PlatformCommission::where('order_id', $orderId)->first();

        if ($commission === null) {
            throw new Exception('Este pedido no tiene una venta registrada en la plataforma.', 404);
        }

        // Se suben ANTES de abrir la transaccion: mover ficheros puede tardar, y mantener
        // abierta una transaccion mientras se escriben 50MB en disco bloquea la fila para
        // todos los demas.
        $subidos = $this->uploader->uploadMultiple($files, 'delivery-evidence');

        if ($subidos === []) {
            throw new Exception('No se recibió ninguna evidencia válida.', 422);
        }

        return DB::transaction(function () use ($orderId, $commission, $subidos) {
            $expediente = OrderDeliveryConfirmation::where('tenant_id', $commission->tenant_id)
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if ($expediente === null) {
                // El comerciante puede aportar evidencia al enviar, antes de declarar la
                // entrega, asi que el expediente puede no existir todavia. Nace aqui sin
                // `declared_delivered_at`: el reloj del plazo NO arranca por enviar, solo
                // por declarar la entrega.
                $expediente = new OrderDeliveryConfirmation([
                    'id' => (string) Str::uuid(),
                    'tenant_id' => $commission->tenant_id,
                    'order_id' => $orderId,
                    'central_order_id' => $commission->central_order_id,
                ]);
            }

            if ($expediente->released_at !== null) {
                throw new Exception('Esta entrega ya se cerró; no admite más evidencias.', 409);
            }

            // Se acumula en vez de reemplazar: el comerciante puede subir la foto del
            // paquete el lunes y el comprobante del transportista el martes, y perder la
            // primera al añadir la segunda seria una trampa.
            $expediente->shipment_evidence = array_merge($expediente->shipment_evidence ?? [], $subidos);
            $expediente->save();

            return $expediente;
        });
    }
}
