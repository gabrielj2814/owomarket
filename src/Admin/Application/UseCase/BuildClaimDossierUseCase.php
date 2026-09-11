<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Application\Service\TenantReputation;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;

/**
 * El expediente de una reclamación, para entregárselo a quien denuncia (subsistema 5, fase D).
 *
 * Es la última capa del escalado, y **casi nunca recupera el dinero**: los importes son
 * pequeños y el proceso lento. Su valor está en disuadir —una tienda que sabe que ignorar
 * genera un expediente con su identidad verificada dentro se comporta distinto— y en cerrar el
 * proceso con dignidad para el comprador.
 *
 * **Es entregar un expediente, no acompañar un proceso.** Asesorar legalmente al comprador
 * convertiría a la plataforma en parte del conflicto, y la línea es deliberada.
 *
 * No construye nada: junta lo que los subsistemas 1, 3 y 5 ya guardaron por su cuenta. Que eso
 * sea una simple lectura es la señal de que las piezas anteriores quedaron bien puestas.
 */
final class BuildClaimDossierUseCase
{
    public function __construct(
        private readonly TenantReputation $reputacion
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws Exception 404 si la reclamación no existe.
     */
    public function execute(string $claimId): array
    {
        $reclamacion = CustomerReturnRequest::with('customer:id,name,email,phone,document_id')
            ->find($claimId);

        if ($reclamacion === null) {
            throw new Exception('Reclamación no encontrada.', 404);
        }

        $kyc = TenantKycProfile::where('tenant_id', $reclamacion->tenant_id)->first();
        $entrega = $reclamacion->tenant_order_id === null
            ? null
            : OrderDeliveryConfirmation::where('order_id', $reclamacion->tenant_order_id)->first();

        return [
            'claim' => [
                'id' => $reclamacion->id,
                'order_number' => $reclamacion->order_number,
                'product_name' => $reclamacion->product_name,
                'amount' => (float) $reclamacion->amount,
                'reason' => $reclamacion->reason,
                'description' => $reclamacion->description,
                'photos' => $reclamacion->photos ?? [],
                'status' => $reclamacion->status,
                // La cronologia es la mitad del expediente: cuando se entrego, cuando se
                // reclamo y cuando se resolvio. Sin fechas no hay caso que defender.
                'delivered_at' => $reclamacion->delivered_at?->toIso8601String(),
                'claimed_at' => $reclamacion->created_at?->toIso8601String(),
                'resolved_at' => $reclamacion->resolved_at?->toIso8601String(),
                'resolved_by' => $reclamacion->resolved_by,
                'resolution_notes' => $reclamacion->resolution_notes,
                'platform_covered_amount' => (float) $reclamacion->platform_covered_amount,
            ],
            'customer' => [
                'name' => $reclamacion->customer?->name,
                'email' => $reclamacion->customer_email,
                'phone' => $reclamacion->customer?->phone,
                'document_id' => $reclamacion->customer?->document_id,
            ],
            /*
             * La identidad de la tienda: sin esto una denuncia no tiene contra quien
             * dirigirse, que es el valor principal que la decision le atribuye al KYC.
             *
             * **La cedula y el RIF NO van aqui.** El modelo los oculta al serializar y este
             * expediente no los desentierra: quien tenga que verlos los pide expresamente, con
             * su registro. Meterlos en un payload que acaba en una captura de pantalla o en un
             * ticket de soporte desharia el cifrado por la puerta de atras.
             */
            'store' => [
                'tenant_id' => $reclamacion->tenant_id,
                'legal_name' => $kyc?->legal_name,
                'phone' => $kyc?->phone,
                'address' => $kyc?->address,
                'kyc_status' => $kyc?->status ?? 'missing',
                'reputation' => $this->reputacion->progress($reclamacion->tenant_id),
            ],
            // Lo que la tienda y el comprador aportaron al entregar (subsistema 3). Es la
            // prueba de que el paquete salio y llego, o de que nadie pudo demostrarlo.
            'delivery' => $entrega === null ? null : [
                'declared_delivered_at' => $entrega->declared_delivered_at?->toIso8601String(),
                'confirmed_at' => $entrega->confirmed_at?->toIso8601String(),
                'released_by' => $entrega->released_by,
                'shipment_evidence' => $entrega->shipment_evidence ?? [],
                'confirmation_evidence' => $entrega->confirmation_evidence ?? [],
            ],
        ];
    }
}
