<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\SupportTicket\Application\Service\UploadSupportAttachmentService;
use Src\Marketplace\Infrastructure\Http\Support\ResolvesStorefrontCustomer;
use Src\Monetization\Application\UseCases\ConfirmOrderDeliveryUseCase;
use Src\Shared\Helper\ApiResponse;

/**
 * El comprador del escaparate confirma que recibio su pedido (subsistema 3, fase B).
 *
 * **Esto es lo que libera el dinero del comerciante**, asi que es la puerta que mas importa de
 * las tres. Hasta ahora solo existia en el dominio central: una venta de escaparate no tenia
 * forma de confirmarse y se liberaba siempre por vencimiento del plazo.
 *
 * Es una PUERTA nueva, no un caso de uso nuevo: `ConfirmOrderDeliveryUseCase` compara contra el
 * `customer_id` del expediente y no le importa de donde venga el pedido. Copiar esa logica para
 * el escaparate habria dejado dos caminos hacia el mismo efecto, y uno de ellos mueve dinero.
 *
 * La evidencia sigue siendo OPCIONAL, igual que en el central: exigir una foto para poder
 * confirmar convertiria el tramite en un obstaculo, y un comprador que no confirma libera por
 * plazo igualmente. Lo unico que se lograria es que nadie confirmara nunca.
 */
final class ConfirmStorefrontDeliveryPOSTController
{
    use ResolvesStorefrontCustomer;

    public function __construct(
        private readonly ConfirmOrderDeliveryUseCase $confirmDelivery,
        private readonly UploadSupportAttachmentService $uploader
    ) {}

    public function __invoke(Request $request, string $orderId): JsonResponse
    {
        $customerId = $this->currentStorefrontCustomerId();

        if ($customerId === '') {
            return ApiResponse::error('Inicia sesión para confirmar la entrega.', 401);
        }

        $evidencia = null;
        if ($request->hasFile('evidence')) {
            $request->validate([
                'evidence' => ['array', 'max:10'],
                'evidence.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov'],
            ]);

            $evidencia = $this->uploader->uploadMultiple($request->file('evidence', []), 'delivery-evidence');
        }

        try {
            $expediente = $this->confirmDelivery->execute($orderId, $customerId, $evidencia);

            return ApiResponse::success(
                data: [
                    'order_id' => $expediente->order_id,
                    'confirmed_at' => $expediente->confirmed_at?->toIso8601String(),
                ],
                message: 'Entrega confirmada. Gracias por avisarnos.'
            );
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 400;

            return ApiResponse::error($e->getMessage(), $code >= 400 && $code < 600 ? $code : 400);
        }
    }
}
