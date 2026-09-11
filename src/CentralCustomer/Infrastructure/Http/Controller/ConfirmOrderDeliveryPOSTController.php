<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;
use Src\Monetization\Application\UseCases\ConfirmOrderDeliveryUseCase;
use Src\SupportTicket\Application\Service\UploadSupportAttachmentService;

/**
 * El comprador confirma que recibio lo suyo, y con eso libera el dinero de la tienda
 * (subsistema 3).
 *
 * `{orderId}` es el pedido DE LA TIENDA, no el pedido central: un carrito repartido entre
 * tres tiendas se confirma tres veces, una por tienda, porque cada una entrega por su cuenta
 * y cada una cobra por su cuenta.
 *
 * El comprador sale del `auth:central_customer`, **nunca de la peticion**: es lo unico que
 * impide que cualquiera libere el dinero de un pedido ajeno pasando otro identificador.
 */
final class ConfirmOrderDeliveryPOSTController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly ConfirmOrderDeliveryUseCase $confirmDelivery,
        private readonly UploadSupportAttachmentService $uploader
    ) {}

    public function __invoke(Request $request, string $orderId): JsonResponse
    {
        $customerId = $this->currentCustomerId();

        // La evidencia del comprador es OPCIONAL a proposito: exigirle una foto para poder
        // confirmar convertiria el tramite en un obstaculo, y un comprador que no confirma
        // libera por plazo igualmente. Se pide, no se impone.
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

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Entrega confirmada. Gracias por avisarnos.',
                'data' => [
                    'order_id' => $expediente->order_id,
                    'confirmed_at' => $expediente->confirmed_at?->toIso8601String(),
                ],
            ]);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) ? (int) $e->getCode() : 400;
            $status = $code >= 400 && $code < 600 ? $code : 400;

            return response()->json([
                'code' => $status,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
