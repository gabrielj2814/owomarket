import OrderServices, { DeliveryStatus } from '@/Services/OrderServices';
import { Button, FileInput, Label } from 'flowbite-react';
import React, { useEffect, useState } from 'react';

/**
 * El comerciante deja constancia de que envió el pedido, con fotos o video (subsistema 3).
 *
 * Conviene tener claro lo que esta tarjeta **no** hace: no libera dinero. Desde la fase A, el
 * importe de una venta lo libera el comprador al confirmar que recibió, o el plazo al vencer —
 * antes lo liberaba el propio comerciante declarando su entrega, que era la puerta.
 *
 * Lo que sí hace es que «lo envié» contra «no me llegó» deje de ser la palabra de uno contra
 * la del otro. El comprador ve esta misma evidencia desde su portal.
 */
interface ShipmentEvidenceCardProps {
    orderId: string;
    onUploaded?: () => void;
}

const ShipmentEvidenceCard: React.FC<ShipmentEvidenceCardProps> = ({ orderId, onUploaded }) => {
    const [status, setStatus] = useState<DeliveryStatus | null>(null);
    const [files, setFiles] = useState<File[]>([]);
    const [sending, setSending] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const cargar = async () => {
        const res = await OrderServices.getDeliveryStatus(orderId);
        setStatus(res?.data ?? null);
    };

    useEffect(() => {
        void cargar();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [orderId]);

    const subir = async () => {
        if (files.length === 0) return;

        setSending(true);
        setError(null);

        const res = await OrderServices.attachShipmentEvidence(orderId, files);

        if (res.status === 'error') {
            setError(res.message ?? 'No se pudo adjuntar la evidencia.');
        } else {
            setFiles([]);
            await cargar();
            onUploaded?.();
        }

        setSending(false);
    };

    const cerrada = status?.released_at != null;

    return (
        <div className="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
            <h3 className="mb-1 text-sm font-black tracking-wider text-gray-900 uppercase dark:text-white">
                Evidencia del envío
            </h3>
            <p className="mb-3 text-xs text-gray-500 dark:text-gray-400">
                Adjunta fotos o video del paquete antes de enviarlo. El comprador las verá en su portal.
            </p>

            {status && status.shipment_evidence.length > 0 && (
                <div className="mb-3 flex flex-wrap gap-2">
                    {status.shipment_evidence.map((file, i) =>
                        file.type === 'video' ? (
                            <video key={i} src={file.url} controls className="h-20 w-20 rounded-lg object-cover" />
                        ) : (
                            <a key={i} href={file.url} target="_blank" rel="noreferrer">
                                <img src={file.url} alt={file.original_name || 'Evidencia'} className="h-20 w-20 rounded-lg object-cover" />
                            </a>
                        ),
                    )}
                </div>
            )}

            {cerrada ? (
                <p data-testid="evidence-closed" className="text-xs font-bold text-gray-500 dark:text-gray-400">
                    Esta entrega ya se cerró y no admite más evidencias.
                </p>
            ) : (
                <div className="space-y-2">
                    <Label htmlFor={`shipment-evidence-${orderId}`} className="sr-only">
                        Adjuntar evidencia del envío
                    </Label>
                    <FileInput
                        id={`shipment-evidence-${orderId}`}
                        multiple
                        accept="image/*,video/*"
                        onChange={(e) => setFiles(Array.from(e.target.files ?? []))}
                    />

                    {error && <p className="text-xs font-bold text-red-600">{error}</p>}

                    {/* `blue` de Flowbite y no `primary` del tema: este componente se usa desde
                        el backoffice de la tienda, que no esta envuelto en el tema del panel. */}
                    <Button type="button" color="blue" size="xs" onClick={subir} disabled={sending || files.length === 0}>
                        {sending ? 'Subiendo…' : 'Adjuntar evidencia'}
                    </Button>
                </div>
            )}
        </div>
    );
};

export default ShipmentEvidenceCard;
