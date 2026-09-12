import Dashboard from '@/components/layouts/Dashboard';
import TenantOwnerNavTabs from '@/components/tenant/TenantOwnerNavTabs';
import TenantReturnServices, { TenantReturn } from '@/Services/TenantReturnServices';
import { Head } from '@inertiajs/react';
import {
    Badge,
    Button,
    Card,
    Label,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    Select,
    Spinner,
    Textarea,
} from 'flowbite-react';
import React, { FC, useCallback, useEffect, useState } from 'react';
import {
    HiOutlineArrowPath,
    HiOutlineChatBubbleLeftRight,
    HiOutlineClock,
    HiOutlineExclamationTriangle,
} from 'react-icons/hi2';

/**
 * Reclamaciones de la tienda (subsistema 5, vista 2 de
 * `planes/por_hacer/PLAN_VISTAS_PENDIENTES.md`).
 *
 * **Sin esta pantalla el reloj aprobaba todo por silencio.** `returns:auto-resolve` corre a
 * diario y resuelve a favor del comprador lo que la tienda no responde en plazo; como no había
 * dónde responder, cada reclamación revertía una venta sin que nadie la mirara.
 *
 * Tres cosas que esta pantalla tiene que decir, y que no son adorno:
 *
 * 1. **Cuántos días quedan.** Es el dato que hace que la pantalla se use. Viene del backend
 *    porque el plazo es un ajuste central que el navegador no ve.
 * 2. **Qué cuesta aprobar.** El importe sale del saldo del comerciante. Se dice antes de que
 *    pulse, no después.
 * 3. **Qué cuesta callar.** Una reclamación resuelta por silencio cuenta como «sin responder»
 *    y baja el nivel de reputación a bajo, lo que **duplica la retención de todas sus ventas**.
 */
interface TenantOption {
    id: string;
    name: string;
}

interface TenantReturnsPageProps {
    title?: string;
    user_id: string;
    tenants: TenantOption[];
    response_days: number;
}

const MOTIVOS: Record<string, string> = {
    defectuoso: 'Producto defectuoso',
    no_corresponde: 'No corresponde con lo pedido',
    danado: 'Llegó dañado',
    otro: 'Otro motivo',
};

const ESTADO: Record<string, { texto: string; color: string }> = {
    requested: { texto: 'Sin responder', color: 'warning' },
    in_review: { texto: 'En revisión', color: 'warning' },
    approved: { texto: 'Aprobada', color: 'failure' },
    rejected: { texto: 'Rechazada', color: 'success' },
};

const fecha = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString('es-VE') : '—');

const dinero = (valor: number) =>
    new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'USD' }).format(valor);

const TenantReturnsPage: FC<TenantReturnsPageProps> = ({
    title = 'Reclamaciones de mis tiendas - OwOMarket',
    user_id,
    tenants = [],
    response_days,
}) => {
    const [tiendaId, setTiendaId] = useState<string>(tenants[0]?.id ?? '');
    const [reclamaciones, setReclamaciones] = useState<TenantReturn[]>([]);
    const [cargando, setCargando] = useState(false);

    const [resolviendo, setResolviendo] = useState<TenantReturn | null>(null);
    const [aprobando, setAprobando] = useState(false);
    const [notas, setNotas] = useState('');
    const [errorNotas, setErrorNotas] = useState<string | null>(null);
    const [enviando, setEnviando] = useState(false);

    const [aviso, setAviso] = useState<{ tipo: 'ok' | 'error'; texto: string } | null>(null);

    const cargar = useCallback(async (id: string) => {
        if (!id) return;
        setCargando(true);
        try {
            const respuesta = await TenantReturnServices.listar(id);
            setReclamaciones(respuesta.data ?? []);
        } catch {
            setAviso({ tipo: 'error', texto: 'No se pudieron cargar las reclamaciones. Vuelve a intentarlo.' });
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        void cargar(tiendaId);
    }, [tiendaId, cargar]);

    const abrir = (reclamacion: TenantReturn, paraAprobar: boolean) => {
        setResolviendo(reclamacion);
        setAprobando(paraAprobar);
        setNotas('');
        setErrorNotas(null);
    };

    const cerrar = () => {
        setResolviendo(null);
        setNotas('');
        setErrorNotas(null);
    };

    const confirmar = async () => {
        if (!resolviendo) return;

        if (!aprobando && notas.trim() === '') {
            // Se comprueba aquí además de en el backend para señalar el campo en vez de soltar
            // un toast genérico: el comprador verá este texto y es lo único que tendrá.
            setErrorNotas('Indica el motivo del rechazo: el comprador verá este texto.');
            return;
        }

        setEnviando(true);
        setErrorNotas(null);
        try {
            const respuesta = await TenantReturnServices.resolver(resolviendo.id, {
                approved: aprobando,
                notes: notas.trim() || undefined,
            });

            setAviso({ tipo: 'ok', texto: respuesta.message });
            cerrar();
            await cargar(tiendaId);
        } catch (error: unknown) {
            const respuesta = (
                error as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } }
            ).response;

            if (respuesta?.status === 422) {
                setErrorNotas(respuesta.data?.errors?.notes?.[0] ?? respuesta.data?.message ?? 'Revisa el motivo.');
            } else if (respuesta?.status === 409) {
                // El reloj se adelantó entre que se cargó la lista y se pulsó el botón.
                setAviso({
                    tipo: 'error',
                    texto: 'Esta reclamación ya está resuelta: venció el plazo y se resolvió a favor del comprador.',
                });
                cerrar();
                await cargar(tiendaId);
            } else {
                setAviso({
                    tipo: 'error',
                    texto: respuesta?.data?.message ?? 'No se pudo registrar la respuesta. Vuelve a intentarlo.',
                });
                cerrar();
            }
        } finally {
            setEnviando(false);
        }
    };

    const abiertas = reclamaciones.filter((r) => r.is_open);

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />

            <TenantOwnerNavTabs userId={user_id} activeTab="returns" />

            <div className="mb-4">
                <h1 className="flex items-center gap-2 text-2xl font-black text-gray-900 dark:text-white">
                    <HiOutlineChatBubbleLeftRight className="h-7 w-7 text-blue-600" />
                    Reclamaciones
                </h1>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Tienes <strong>{response_days} días</strong> para responder cada reclamación. Pasado el plazo se
                    resuelve sola a favor del comprador.
                </p>
            </div>

            {abiertas.length > 0 && (
                <div
                    data-testid="aviso-reputacion"
                    className="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300"
                >
                    <span className="font-black">
                        {abiertas.length === 1
                            ? 'Tienes 1 reclamación sin responder.'
                            : `Tienes ${abiertas.length} reclamaciones sin responder.`}
                    </span>{' '}
                    Una reclamación que vence sin respuesta cuenta como «sin responder» y baja tu nivel de reputación a
                    bajo, lo que <strong>duplica la retención de todas tus ventas</strong>. Responder —aunque sea para
                    rechazar— lo evita.
                </div>
            )}

            {aviso && (
                <div
                    data-testid="returns-aviso"
                    role="status"
                    className={`mb-4 rounded-2xl border px-4 py-3 text-sm ${
                        aviso.tipo === 'ok'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300'
                            : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300'
                    }`}
                >
                    {aviso.texto}
                </div>
            )}

            <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                {tenants.length > 1 && (
                    <div className="sm:w-72">
                        <Label htmlFor="returns-tienda">Tienda</Label>
                        <Select id="returns-tienda" value={tiendaId} onChange={(e) => setTiendaId(e.target.value)}>
                            {tenants.map((t) => (
                                <option key={t.id} value={t.id}>
                                    {t.name}
                                </option>
                            ))}
                        </Select>
                    </div>
                )}
                <Button color="light" onClick={() => void cargar(tiendaId)} disabled={cargando}>
                    <HiOutlineArrowPath className={`mr-2 h-4 w-4 ${cargando ? 'animate-spin' : ''}`} />
                    Actualizar
                </Button>
            </div>

            {cargando && reclamaciones.length === 0 ? (
                <div className="flex justify-center py-10">
                    <Spinner aria-label="Cargando reclamaciones" />
                </div>
            ) : tenants.length === 0 ? (
                <Card>
                    <p data-testid="returns-sin-tienda" className="py-6 text-center text-sm text-gray-500">
                        Todavía no tienes ninguna tienda. Las reclamaciones de tus compradores aparecerán aquí cuando la
                        tengas.
                    </p>
                </Card>
            ) : reclamaciones.length === 0 ? (
                <Card>
                    <p data-testid="returns-vacio" className="py-6 text-center text-sm text-gray-500">
                        No hay reclamaciones. Aparecerán aquí cuando un comprador reclame una compra de esta tienda.
                    </p>
                </Card>
            ) : (
                <div className="space-y-3">
                    {reclamaciones.map((reclamacion) => {
                        const etiqueta = ESTADO[reclamacion.status] ?? { texto: reclamacion.status, color: 'gray' };
                        const urgente = reclamacion.is_open && (reclamacion.days_left ?? 0) <= 1;

                        return (
                            <Card key={reclamacion.id} data-testid={`reclamacion-${reclamacion.id}`}>
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-black text-gray-900 dark:text-white">
                                                {reclamacion.product_name}
                                            </span>
                                            <Badge color={etiqueta.color} className="w-fit">
                                                {etiqueta.texto}
                                            </Badge>
                                            <span className="text-xs text-gray-400">
                                                Pedido {reclamacion.order_number} · {fecha(reclamacion.created_at)}
                                            </span>
                                        </div>

                                        <p className="mt-1 text-sm font-bold text-gray-700 dark:text-gray-300">
                                            {dinero(reclamacion.amount)} · {MOTIVOS[reclamacion.reason] ?? reclamacion.reason}
                                        </p>

                                        {reclamacion.description && (
                                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {reclamacion.description}
                                            </p>
                                        )}

                                        {reclamacion.photos.length > 0 && (
                                            <div className="mt-2 flex flex-wrap gap-2">
                                                {reclamacion.photos.map((foto, i) => (
                                                    <a key={foto} href={foto} target="_blank" rel="noopener noreferrer">
                                                        <img
                                                            src={foto}
                                                            alt={`Foto ${i + 1} de la reclamación`}
                                                            className="h-16 w-16 rounded-lg border border-gray-200 object-cover dark:border-gray-700"
                                                        />
                                                    </a>
                                                ))}
                                            </div>
                                        )}

                                        {!reclamacion.is_open && reclamacion.resolved_by === 'timeout' && (
                                            <p
                                                data-testid={`por-silencio-${reclamacion.id}`}
                                                className="mt-2 text-xs font-semibold text-red-600 dark:text-red-400"
                                            >
                                                Se resolvió sola: venció el plazo sin respuesta.
                                            </p>
                                        )}

                                        {!reclamacion.is_open && reclamacion.resolution_notes && (
                                            <p className="mt-1 text-xs text-gray-500">
                                                Motivo registrado: {reclamacion.resolution_notes}
                                            </p>
                                        )}
                                    </div>

                                    <div className="shrink-0 sm:text-right">
                                        {reclamacion.is_open && reclamacion.days_left !== null && (
                                            <p
                                                data-testid={`reloj-${reclamacion.id}`}
                                                className={`mb-2 flex items-center gap-1 text-xs font-black sm:justify-end ${
                                                    urgente
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : 'text-amber-600 dark:text-amber-400'
                                                }`}
                                            >
                                                <HiOutlineClock className="h-4 w-4" />
                                                {reclamacion.days_left === 0
                                                    ? 'El plazo vence hoy'
                                                    : reclamacion.days_left === 1
                                                      ? 'Queda 1 día para responder'
                                                      : `Quedan ${reclamacion.days_left} días para responder`}
                                            </p>
                                        )}

                                        {reclamacion.is_open ? (
                                            <div className="flex gap-2 sm:justify-end">
                                                <Button size="xs" color="failure" onClick={() => abrir(reclamacion, true)}>
                                                    Aprobar
                                                </Button>
                                                <Button size="xs" color="success" onClick={() => abrir(reclamacion, false)}>
                                                    Rechazar
                                                </Button>
                                            </div>
                                        ) : (
                                            <span className="text-xs text-gray-400">Ya resuelta</span>
                                        )}
                                    </div>
                                </div>
                            </Card>
                        );
                    })}
                </div>
            )}

            <Modal show={resolviendo !== null} onClose={cerrar} size="lg">
                <ModalHeader>{aprobando ? 'Aprobar reclamación' : 'Rechazar reclamación'}</ModalHeader>
                <ModalBody>
                    {resolviendo && (
                        <div className="space-y-4 text-sm">
                            <div className="rounded-xl bg-gray-50 p-3 dark:bg-gray-800">
                                <p className="font-bold text-gray-900 dark:text-white">{resolviendo.product_name}</p>
                                <p className="text-gray-600 dark:text-gray-400">
                                    {dinero(resolviendo.amount)} · pedido {resolviendo.order_number}
                                </p>
                            </div>

                            {aprobando ? (
                                <div className="flex gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                                    <HiOutlineExclamationTriangle className="h-5 w-5 shrink-0" />
                                    <p>
                                        Aprobar <strong>revierte la venta</strong>: {dinero(resolviendo.amount)} vuelven al
                                        comprador y salen de tu saldo. Esta acción no se puede deshacer.
                                    </p>
                                </div>
                            ) : (
                                <div>
                                    <Label htmlFor="returns-motivo">Motivo del rechazo</Label>
                                    <Textarea
                                        id="returns-motivo"
                                        rows={3}
                                        value={notas}
                                        color={errorNotas ? 'failure' : undefined}
                                        onChange={(e) => {
                                            setNotas(e.target.value);
                                            setErrorNotas(null);
                                        }}
                                        placeholder="Ej.: el producto se entregó en perfecto estado, con firma de recepción."
                                    />
                                    {errorNotas && (
                                        <p role="alert" className="mt-1 text-xs font-semibold text-red-600">
                                            {errorNotas}
                                        </p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        El comprador verá este texto. Es lo único que tendrá para entender tu decisión.
                                    </p>
                                </div>
                            )}

                            {aprobando && (
                                <div>
                                    <Label htmlFor="returns-notas">Nota para el comprador (opcional)</Label>
                                    <Textarea
                                        id="returns-notas"
                                        rows={2}
                                        value={notas}
                                        onChange={(e) => setNotas(e.target.value)}
                                    />
                                </div>
                            )}
                        </div>
                    )}
                </ModalBody>
                <ModalFooter>
                    <Button
                        color={aprobando ? 'failure' : 'success'}
                        onClick={() => void confirmar()}
                        disabled={enviando}
                    >
                        {enviando && <Spinner size="sm" className="mr-2" />}
                        {aprobando ? 'Aprobar' : 'Rechazar'}
                    </Button>
                    <Button color="light" onClick={cerrar} disabled={enviando}>
                        Cancelar
                    </Button>
                </ModalFooter>
            </Modal>
        </Dashboard>
    );
};

export default TenantReturnsPage;
