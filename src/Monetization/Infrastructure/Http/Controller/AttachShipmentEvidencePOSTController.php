<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Monetization\Application\UseCases\AttachShipmentEvidenceUseCase;
use Src\Monetization\Infrastructure\Http\Request\AttachDeliveryEvidenceFormRequest;

/**
 * El comerciante adjunta la prueba de que envio el pedido (subsistema 3, fase B).
 *
 * Vive bajo `tenantApi`, igual que el resto de acciones del comerciante sobre sus pedidos.
 * Que este en la API de la tienda ya no es un problema como lo era la entrega: **subir una
 * foto no libera dinero.** Solo deja constancia.
 */
final class AttachShipmentEvidencePOSTController
{
    public function __construct(
        private readonly AttachShipmentEvidenceUseCase $attachEvidence
    ) {}

    public function __invoke(AttachDeliveryEvidenceFormRequest $request, string $orderId): JsonResponse
    {
        try {
            $expediente = $this->attachEvidence->execute($orderId, $request->file('evidence', []));

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Evidencia de envío registrada.',
                'data' => ['shipment_evidence' => $expediente->shipment_evidence],
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
