import CustomerPortalServices, { DeliveryEvidenceFile, DeliveryStatusData } from '@/Services/CustomerPortalServices';
import React, { useEffect, useState } from 'react';

/**
 * El expediente de entrega de una tienda, visto por el comprador (subsistema 3).
 *
 * Hace dos cosas y conviene no confundirlas: **enseña** lo que la tienda subió al enviar, y
 * **permite confirmar** la recepción. Lo segundo es lo que libera el dinero del comerciante —
 * hasta la fase A eso lo hacía el propio comerciante desde su API.
 *
 * `orderId` es el pedido DE LA TIENDA, no el pedido central: un carrito repartido entre tres
 * tiendas muestra tres paneles.
 *
 * La evidencia del comprador es opcional a propósito. Exigir una foto para poder confirmar
 * convertiría el trámite en un obstáculo, y un comprador que no confirma libera por plazo
 * igualmente: lo único que se lograría es que nadie confirmara nunca.
 */
/**
 * Las dos llamadas que el panel necesita.
 *
 * Se inyectan porque el MISMO panel sirve al portal central y al escaparate de una tienda, y
 * la diferencia entre los dos es solo de donde sale la identidad del comprador: el guard
 * `central_customer` en uno, la sesion del SSO en el otro. Copiar el componente para cambiar
 * dos URLs habria dejado dos versiones que se separan en cuanto alguien toque una.
 */
export interface DeliveryApi {
    getDeliveryStatus: (orderId: string) => Promise<{ data?: DeliveryStatusData | null } | null>;
    confirmDelivery: (orderId: string, files: File[]) => Promise<unknown>;
}

interface DeliveryConfirmationPanelProps {
    orderId: string;
    storeName?: string | null;
    onConfirmed?: () => void;
    /** Por defecto, el portal central. El escaparate pasa el suyo. */
    api?: DeliveryApi;
}

function EvidenceGallery({ files, emptyLabel }: { files: DeliveryEvidenceFile[]; emptyLabel: string }) {
    if (files.length === 0) {
        return <p className="text-xs text-gray-400">{emptyLabel}</p>;
    }

    return (
        <div className="flex flex-wrap gap-2">
            {files.map((file, i) =>
                file.type === 'video' ? (
                    <video
                        key={i}
                        src={file.url}
                        controls
                        className="h-24 w-24 rounded-lg border border-gray-200 object-cover dark:border-gray-700"
                    />
                ) : (
                    <a key={i} href={file.url} target="_blank" rel="noreferrer">
                        <img
                            src={file.url}
                            alt={file.original_name || 'Evidencia'}
                            className="h-24 w-24 rounded-lg border border-gray-200 object-cover dark:border-gray-700"
                        />
                    </a>
                ),
            )}
        </div>
    );
}

const DeliveryConfirmationPanel: React.FC<DeliveryConfirmationPanelProps> = ({
    orderId,
    storeName,
    onConfirmed,
    api = CustomerPortalServices,
}) => {
    const [status, setStatus] = useState<DeliveryStatusData | null>(null);
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);
    const [files, setFiles] = useState<File[]>([]);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let cancelled = false;

        api.getDeliveryStatus(orderId)
            .then((res) => {
                if (!cancelled) setStatus(res?.data ?? null);
            })
            .catch(() => {
                // Un pedido sin expediente todavia no es un error que mostrar: la tienda aun
                // no ha registrado nada.
                if (!cancelled) setStatus(null);
            })
            .finally(() => {
                if (!cancelled) setLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, [orderId, api]);

    if (loading || status === null) {
        return null;
    }

    const confirmar = async () => {
        setSending(true);
        setError(null);

        try {
            await api.confirmDelivery(orderId, files);
            const res = await api.getDeliveryStatus(orderId);
            setStatus(res?.data ?? null);
            setFiles([]);
            onConfirmed?.();
        } catch (e: any) {
            setError(e?.response?.data?.message ?? 'No se pudo confirmar la entrega. Inténtalo de nuevo.');
        } finally {
            setSending(false);
        }
    };

    return (
        <div className="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
            <h4 className="mb-3 text-xs font-black tracking-wider text-gray-900 uppercase dark:text-white">
                Entrega{storeName ? ` · ${storeName}` : ''}
            </h4>

            <div className="mb-4">
                <p className="mb-2 text-xs font-bold text-gray-500 dark:text-gray-400">Evidencia del envío</p>
                <EvidenceGallery files={status.shipment_evidence} emptyLabel="La tienda no adjuntó evidencia del envío." />
            </div>

            {status.released_at ? (
                <p
                    data-testid="delivery-closed"
                    className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                >
                    {status.released_by === 'customer'
                        ? 'Confirmaste la recepción de este pedido.'
                        : 'Se dio por recibido al cumplirse el plazo.'}
                </p>
            ) : status.can_confirm ? (
                <div className="space-y-3">
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                        La tienda marcó este pedido como entregado. Confírmanos que lo recibiste; si no lo haces, se dará por
                        recibido automáticamente al cumplirse el plazo.
                    </p>

                    <div>
                        <label htmlFor={`evidence-${orderId}`} className="mb-1 block text-xs font-bold text-gray-500 dark:text-gray-400">
                            Adjuntar fotos o video (opcional)
                        </label>
                        <input
                            id={`evidence-${orderId}`}
                            type="file"
                            multiple
                            accept="image/*,video/*"
                            onChange={(e) => setFiles(Array.from(e.target.files ?? []))}
                            className="block w-full text-xs text-gray-500"
                        />
                    </div>

                    {error && <p className="text-xs font-bold text-red-600">{error}</p>}

                    <button
                        type="button"
                        onClick={confirmar}
                        disabled={sending}
                        className="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                    >
                        {sending ? 'Confirmando…' : 'Confirmar que lo recibí'}
                    </button>
                </div>
            ) : (
                <p className="text-xs text-gray-500 dark:text-gray-400">
                    La tienda todavía no ha marcado este pedido como entregado.
                </p>
            )}
        </div>
    );
};

export default DeliveryConfirmationPanel;
