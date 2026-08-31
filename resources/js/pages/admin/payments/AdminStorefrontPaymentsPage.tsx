import Dashboard from '@/components/layouts/Dashboard';
import AdminStorefrontPaymentServices, { PendingPayment, PendingPaymentsPage } from '@/Services/AdminStorefrontPaymentServices';
import { Head } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    Pagination,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
    TextInput,
    Textarea,
} from 'flowbite-react';
import React, { FC, useState } from 'react';
import { HiCheckCircle, HiHome, HiOutlineExclamation, HiRefresh, HiSearch } from 'react-icons/hi';
import { TbBuildingBank } from 'react-icons/tb';

interface Props {
    title: string;
    user_id: string;
    payments_data: PendingPaymentsPage;
    metrics: { pending_count: number; pending_ves: number };
    filters: { tenant_id: string; search: string };
}

const bolivares = (valor: number | null) =>
    valor === null ? '—' : `Bs. ${valor.toLocaleString('es-VE', { minimumFractionDigits: 2 })}`;

/**
 * Cobros del escaparate pendientes de confirmar.
 *
 * Desde que la plataforma cobra todas las ventas, el comerciante ya no puede decir si el dinero
 * llegó: no tiene acceso a ese extracto bancario. Ésta es la pantalla donde el administrador
 * coteja cada referencia contra su banco y confirma.
 *
 * Hasta que existió, los endpoints de la Fase 3a no los llamaba nadie y confirmar un cobro sólo
 * se podía hacer por HTTP a mano.
 */
const AdminStorefrontPaymentsPage: FC<Props> = ({ title, user_id, payments_data, metrics, filters }) => {
    const [payments, setPayments] = useState<PendingPaymentsPage>(payments_data);
    const [resumen, setResumen] = useState(metrics);
    const [search, setSearch] = useState(filters.search ?? '');
    const [cargando, setCargando] = useState(false);
    const [aviso, setAviso] = useState<{ tipo: 'success' | 'error'; texto: string } | null>(null);

    const [seleccionado, setSeleccionado] = useState<PendingPayment | null>(null);
    const [referencia, setReferencia] = useState('');
    const [notas, setNotas] = useState('');
    const [confirmando, setConfirmando] = useState(false);

    const recargar = async (page = 1) => {
        setCargando(true);
        try {
            const respuesta = await AdminStorefrontPaymentServices.listar({ search: search.trim() || undefined, page });
            if (respuesta?.status === 'success') {
                setPayments(respuesta.data.payments);
                setResumen(respuesta.data.metrics);
            }
        } catch {
            setAviso({ tipo: 'error', texto: 'No se pudo cargar la lista de cobros.' });
        } finally {
            setCargando(false);
        }
    };

    const abrirConfirmacion = (pago: PendingPayment) => {
        setSeleccionado(pago);
        // Se precarga la del comprador: es la que el administrador acaba de cotejar. Si en el
        // banco aparece otra, la sobrescribe y quedan las dos guardadas.
        setReferencia(pago.payment_reference ?? pago.reported_reference ?? '');
        setNotas('');
    };

    const confirmar = async () => {
        if (!seleccionado) return;

        setConfirmando(true);
        try {
            const respuesta = await AdminStorefrontPaymentServices.confirmar(seleccionado.id, {
                reference: referencia.trim() || undefined,
                notes: notas.trim() || undefined,
            });

            if (respuesta?.status === 'success') {
                setAviso({ tipo: 'success', texto: respuesta.message });
                setSeleccionado(null);
                void recargar(payments.current_page);
            }
        } catch (error: any) {
            setAviso({ tipo: 'error', texto: error.response?.data?.message ?? 'No se pudo confirmar el cobro.' });
        } finally {
            setConfirmando(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />

            <div className="space-y-4 p-4">
                <Breadcrumb>
                    <BreadcrumbItem icon={HiHome}>Inicio</BreadcrumbItem>
                    <BreadcrumbItem>Cobros por Confirmar</BreadcrumbItem>
                </Breadcrumb>

                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-black text-gray-900 dark:text-white">Cobros por Confirmar</h1>
                    <p className="text-xs text-gray-500">
                        El dinero de estas ventas entra en la cuenta de la plataforma. Coteja cada referencia contra el
                        extracto del banco antes de confirmar: al confirmar, el importe pasa a la billetera de la tienda.
                    </p>
                </div>

                {aviso && (
                    <div
                        className={`rounded-2xl px-4 py-3 text-xs ${
                            aviso.tipo === 'success'
                                ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
                                : 'bg-red-50 text-red-800 dark:bg-red-950/40 dark:text-red-300'
                        }`}
                    >
                        {aviso.texto}
                    </div>
                )}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Card>
                        <span className="text-[11px] font-bold uppercase tracking-wider text-gray-400">Cobros pendientes</span>
                        <div className="text-3xl font-black text-gray-900 dark:text-white">{resumen.pending_count}</div>
                    </Card>
                    <Card>
                        <span className="text-[11px] font-bold uppercase tracking-wider text-gray-400">
                            Importe pendiente de cotejar
                        </span>
                        <div className="text-3xl font-black text-amber-600">{bolivares(resumen.pending_ves)}</div>
                    </Card>
                </div>

                <Card>
                    <div className="mb-3 flex flex-col gap-2 sm:flex-row">
                        <TextInput
                            className="flex-1"
                            icon={HiSearch}
                            placeholder="Número de pedido…"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && void recargar(1)}
                        />
                        <Button color="gray" onClick={() => void recargar(1)} disabled={cargando}>
                            <HiRefresh className="mr-1 h-4 w-4" />
                            Buscar
                        </Button>
                    </div>

                    {payments.data.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-gray-300 py-10 text-center dark:border-gray-700">
                            <HiCheckCircle className="mx-auto h-10 w-10 text-emerald-400" />
                            <p className="mt-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                No hay cobros pendientes de confirmar.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table hoverable>
                                <TableHead>
                                    <TableRow>
                                        <TableHeadCell>Pedido</TableHeadCell>
                                        <TableHeadCell>Tienda</TableHeadCell>
                                        <TableHeadCell>Referencia</TableHeadCell>
                                        <TableHeadCell className="text-right">A cotejar</TableHeadCell>
                                        <TableHeadCell className="text-center">Acción</TableHeadCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody className="divide-y">
                                    {payments.data.map((pago) => (
                                        <TableRow key={pago.id} className="bg-white dark:bg-gray-800">
                                            <TableCell>
                                                <div className="font-bold text-gray-900 dark:text-white">{pago.order_number}</div>
                                                <div className="text-[11px] text-gray-400">
                                                    {pago.created_at ? new Date(pago.created_at).toLocaleString('es-VE') : '—'}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-gray-700 dark:text-gray-300">{pago.tenant_name ?? pago.tenant_id}</div>
                                                <Badge color="gray" className="mt-1 inline-block">
                                                    {pago.source === 'central_marketplace' ? 'Marketplace' : 'Escaparate'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="font-mono text-xs text-gray-900 dark:text-white">
                                                    {pago.payment_reference ?? '—'}
                                                </div>
                                                {/* La del comerciante va aparte y etiquetada: es una pista de que el
                                                    comprador le escribió por otro canal, no un hecho comprobado. */}
                                                {pago.reported_reference && (
                                                    <div className="mt-1 font-mono text-[11px] text-amber-600">
                                                        Reportada por la tienda: {pago.reported_reference}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="font-black text-gray-900 dark:text-white">{bolivares(pago.total_ves)}</div>
                                                {pago.total_ves === null ? (
                                                    <div className="text-[11px] text-red-500">Sin tasa registrada</div>
                                                ) : (
                                                    <div className="text-[11px] text-gray-400">${pago.order_total.toFixed(2)} USD</div>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Button size="xs" color="success" onClick={() => abrirConfirmacion(pago)}>
                                                    Confirmar
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {payments.last_page > 1 && (
                                <div className="mt-4 flex justify-center">
                                    <Pagination
                                        currentPage={payments.current_page}
                                        totalPages={payments.last_page}
                                        onPageChange={(p) => void recargar(p)}
                                    />
                                </div>
                            )}
                        </div>
                    )}
                </Card>
            </div>

            <Modal show={seleccionado !== null} onClose={() => setSeleccionado(null)} size="lg">
                <ModalHeader>Confirmar cobro recibido</ModalHeader>
                <ModalBody>
                    {seleccionado && (
                        <div className="space-y-4 text-sm">
                            <div className="flex items-start gap-2 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                <HiOutlineExclamation className="mt-0.5 h-4 w-4 shrink-0" />
                                <span>
                                    Confirma sólo si ya viste el ingreso en el extracto. Al confirmar, el importe pasa a la
                                    billetera de la tienda y no se puede confirmar dos veces.
                                </span>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <p className="text-[11px] uppercase text-gray-400">Pedido</p>
                                    <p className="font-bold text-gray-900 dark:text-white">{seleccionado.order_number}</p>
                                </div>
                                <div>
                                    <p className="text-[11px] uppercase text-gray-400">Tienda</p>
                                    <p className="font-bold text-gray-900 dark:text-white">
                                        {seleccionado.tenant_name ?? seleccionado.tenant_id}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-[11px] uppercase text-gray-400">Importe a buscar</p>
                                    <p className="flex items-center gap-1 text-lg font-black text-emerald-600">
                                        <TbBuildingBank className="h-4 w-4" />
                                        {bolivares(seleccionado.total_ves)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-[11px] uppercase text-gray-400">Método</p>
                                    <p className="font-bold capitalize text-gray-900 dark:text-white">
                                        {seleccionado.payment_gateway?.replace('_', ' ') ?? '—'}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-bold text-gray-700 dark:text-gray-300">
                                    Referencia cotejada
                                </label>
                                <TextInput value={referencia} onChange={(e) => setReferencia(e.target.value)} />
                                <p className="mt-1 text-[11px] text-gray-400">
                                    Viene precargada con la que puso el comprador. Si en el banco aparece otra, cámbiala:
                                    se guardan las dos.
                                </p>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-bold text-gray-700 dark:text-gray-300">Notas</label>
                                <Textarea rows={2} value={notas} onChange={(e) => setNotas(e.target.value)} />
                            </div>
                        </div>
                    )}
                </ModalBody>
                <ModalFooter>
                    <Button color="gray" onClick={() => setSeleccionado(null)} disabled={confirmando}>
                        Cancelar
                    </Button>
                    <Button color="success" onClick={() => void confirmar()} disabled={confirmando}>
                        <HiCheckCircle className="mr-1 h-4 w-4" />
                        {confirmando ? 'Confirmando…' : 'Confirmar cobro'}
                    </Button>
                </ModalFooter>
            </Modal>
        </Dashboard>
    );
};

export default AdminStorefrontPaymentsPage;
