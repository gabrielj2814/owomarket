import TenantKycServices from '@/Services/TenantKycServices';
import { Alert, Button, FileInput, Label, TextInput } from 'flowbite-react';
import React, { useEffect, useState } from 'react';

/**
 * Verificación de identidad del comerciante (subsistema 1).
 *
 * Vive en la wallet a propósito: **es ahí donde el KYC se exige**. Sin identidad verificada el
 * retiro se rechaza, así que la explicación tiene que estar donde aparece el botón que no
 * funciona — no escondida en un ajuste que nadie visita.
 *
 * No muestra la cédula ni el RIF ya enviados. El backend no los devuelve, y aquí tampoco se
 * piden: el comerciante ya los tiene, y tenerlos dando vueltas por la red y por el navegador
 * solo multiplica los sitios de los que pueden escaparse.
 */
interface KycStatus {
    status: 'missing' | 'pending' | 'verified' | 'rejected';
    legal_name?: string | null;
    phone?: string | null;
    address?: string | null;
    has_document: boolean;
    rejection_reason?: string | null;
}

interface TenantKycCardProps {
    tenantId: string;
    onVerified?: () => void;
}

const TenantKycCard: React.FC<TenantKycCardProps> = ({ tenantId, onVerified }) => {
    const [kyc, setKyc] = useState<KycStatus | null>(null);
    const [form, setForm] = useState({ legal_name: '', cedula: '', rif: '', phone: '', address: '' });
    const [document, setDocument] = useState<File | null>(null);
    const [sending, setSending] = useState(false);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [message, setMessage] = useState<string | null>(null);

    const cargar = async () => {
        try {
            // Antes esto era `axios.get('/owner/api/kyc/...')`, sin el prefijo `tenant` de la
            // ruta real: devolvia 404 SIEMPRE, el `catch` lo tragaba y la tarjeta se pintaba
            // como `null`. Resultado: ningun comerciante podia enviar su identidad, y el
            // cobro quedaba bloqueado desde este extremo.
            const res = await TenantKycServices.estado(tenantId);
            const data = res?.data as KycStatus;
            setKyc(data);
            setForm((f) => ({
                ...f,
                legal_name: data?.legal_name ?? f.legal_name,
                phone: data?.phone ?? f.phone,
                address: data?.address ?? f.address,
            }));
        } catch {
            setKyc(null);
        }
    };

    useEffect(() => {
        void cargar();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [tenantId]);

    if (kyc === null) return null;

    if (kyc.status === 'verified') {
        return (
            <Alert data-testid="kyc-verified" color="success" className="mb-4">
                <span className="font-black">Identidad verificada.</span> Puedes solicitar retiros con normalidad.
            </Alert>
        );
    }

    if (kyc.status === 'pending') {
        return (
            <Alert data-testid="kyc-pending" color="warning" className="mb-4">
                <span className="font-black">Verificación en revisión.</span> Te avisaremos en cuanto esté lista. Hasta
                entonces no podrás solicitar retiros.
            </Alert>
        );
    }

    const enviar = async () => {
        setSending(true);
        setErrors({});
        setMessage(null);

        const datos = new FormData();
        Object.entries(form).forEach(([k, v]) => v && datos.append(k, v));
        if (document) datos.append('document', document);

        try {
            const res = await TenantKycServices.enviar(tenantId, datos);
            setMessage(res?.message ?? 'Datos enviados.');
            await cargar();
            onVerified?.();
        } catch (e: any) {
            setErrors(e?.response?.data?.errors ?? {});
            setMessage(e?.response?.data?.message ?? 'No se pudieron enviar los datos.');
        } finally {
            setSending(false);
        }
    };

    const campo = (name: keyof typeof form, label: string, placeholder?: string) => (
        <div>
            <Label htmlFor={`kyc-${name}`}>{label}</Label>
            <TextInput
                id={`kyc-${name}`}
                value={form[name]}
                placeholder={placeholder}
                color={errors[name] ? 'failure' : undefined}
                onChange={(e) => setForm({ ...form, [name]: e.target.value })}
            />
            {errors[name] && <p className="mt-1 text-xs font-bold text-red-600">{errors[name][0]}</p>}
        </div>
    );

    return (
        <Alert data-testid="kyc-form" color="info" className="mb-4">
            <h3 className="mb-1 text-sm font-black">Verifica tu identidad para poder retirar</h3>
            <p className="mb-3 text-xs">
                Necesitamos estos datos antes de enviarte dinero. Puedes seguir vendiendo mientras tanto.
            </p>

            {kyc.status === 'rejected' && kyc.rejection_reason && (
                <p
                    data-testid="kyc-rejection"
                    className="mb-3 rounded-xl bg-red-50 px-3 py-2 font-bold text-red-700 dark:bg-red-950/40 dark:text-red-300"
                >
                    Tu verificación fue rechazada: {kyc.rejection_reason}
                </p>
            )}

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                {campo('legal_name', 'Nombre como aparece en tu cédula')}
                {campo('cedula', 'Cédula', 'V-12345678')}
                {campo('rif', 'RIF (opcional)', 'J-401234567')}
                {campo('phone', 'Teléfono', '+58 412 1234567')}
            </div>

            <div className="mt-3">{campo('address', 'Dirección completa')}</div>

            <div className="mt-3">
                <Label htmlFor="kyc-document">Foto de tu cédula (opcional)</Label>
                <FileInput
                    id="kyc-document"
                    accept="image/*,application/pdf"
                    onChange={(e) => setDocument(e.target.files?.[0] ?? null)}
                />
            </div>

            {message && <p className="mt-3 text-xs font-bold">{message}</p>}

            {/* `blue` y no `primary`: esta tarjeta se pinta en la billetera, dentro del tema
                del panel, pero tambien podria usarse fuera de el. Un color de Flowbite funciona
                en los dos sitios. */}
            <Button type="button" color="blue" size="xs" className="mt-3" onClick={enviar} disabled={sending}>
                {sending ? 'Enviando…' : 'Enviar para verificación'}
            </Button>
        </Alert>
    );
};

export default TenantKycCard;
