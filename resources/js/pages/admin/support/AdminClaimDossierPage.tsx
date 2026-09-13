import Dashboard from '@/components/layouts/Dashboard';
import AdminClaimServices, { AdminClaimRow, ClaimDossier, ClaimListResult, ClaimMetrics, ClaimPagination } from '@/Services/AdminClaimServices';
import { Head } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Label,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    Select,
    Spinner,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
    TextInput,
} from 'flowbite-react';
import { FC, useState } from 'react';
import {
    HiClock,
    HiCurrencyDollar,
    HiDocumentDownload,
    HiExclamation,
    HiHome,
    HiInformationCircle,
    HiRefresh,
    HiScale,
    HiSearch,
} from 'react-icons/hi';

/**
 * Reclamaciones de la plataforma y su expediente (subsistema 5, fase D · vista 5 de
 * `planes/ESTADO_DEL_PROYECTO.md`).
 *
 * **El expediente casi nunca recupera el dinero** —los importes son pequeños y el proceso
 * lento—. Su valor está en disuadir: una tienda que sabe que ignorar genera un documento con su
 * identidad verificada dentro se comporta distinto. Y en cerrar el proceso con dignidad para el
 * comprador, que es lo que la última capa del escalado debe hacer.
 *
 * Dos decisiones de esta pantalla:
 *
 * 1. **El expediente se pide aparte del listado.** La lista no lleva ningún dato de identidad;
 *    abrirla cien veces no mueve nada sensible. El expediente es un clic deliberado.
 * 2. **La cronología es la mitad del documento.** Se presenta como secuencia con fechas porque
 *    es lo que sostiene un caso: cuándo se entregó, cuándo se reclamó, cuándo se resolvió.
 */
interface AdminClaimDossierPageProps {
    title?: string;
    user_id: string;
    claims: AdminClaimRow[];
    pagination: ClaimPagination;
    metrics: ClaimMetrics;
    filters?: { status?: string | null; search?: string | null };
}

const ESTADO: Record<string, { texto: string; color: string }> = {
    requested: { texto: 'Sin responder', color: 'warning' },
    in_review: { texto: 'En revisión', color: 'warning' },
    approved: { texto: 'Aprobada', color: 'failure' },
    rejected: { texto: 'Rechazada', color: 'success' },
    refunded: { texto: 'Reembolsada', color: 'purple' },
};

const fecha = (iso: string | null | undefined) => (iso ? new Date(iso).toLocaleString('es-VE', { dateStyle: 'short', timeStyle: 'short' }) : '—');

const dinero = (valor: number) => `${valor.toFixed(2)} USD`;

const comoSeResolvio = (resolvedBy: string | null | undefined): string => {
    if (!resolvedBy) return 'Todavía sin resolver.';
    if (resolvedBy === 'timeout') return 'Resuelta por vencimiento del plazo: la tienda no respondió.';
    if (resolvedBy === 'merchant') return 'Resuelta por la tienda.';

    return 'Resuelta por el equipo de la plataforma.';
};

const AdminClaimDossierPage: FC<AdminClaimDossierPageProps> = ({
    title = 'Reclamaciones y Expedientes - OwOMarket Admin',
    user_id,
    claims: reclamacionesIniciales = [],
    pagination: paginacionInicial,
    metrics: metricasIniciales,
    filters,
}) => {
    const [reclamaciones, setReclamaciones] = useState<AdminClaimRow[]>(reclamacionesIniciales);
    const [paginacion, setPaginacion] = useState<ClaimPagination>(paginacionInicial);
    const [metricas, setMetricas] = useState<ClaimMetrics>(metricasIniciales);
    const [cargando, setCargando] = useState(false);

    const [filtroEstado, setFiltroEstado] = useState(filters?.status ?? 'open');
    const [busqueda, setBusqueda] = useState(filters?.search ?? '');

    const [expediente, setExpediente] = useState<ClaimDossier | null>(null);
    const [abriendo, setAbriendo] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const recargar = async (pagina = 1, estado = filtroEstado, texto = busqueda) => {
        setCargando(true);
        try {
            const respuesta = await AdminClaimServices.listar({
                status: estado,
                search: texto || undefined,
                page: pagina,
            });
            const datos = respuesta.data as ClaimListResult;
            setReclamaciones(datos.claims);
            setPaginacion(datos.pagination);
            setMetricas(datos.metrics);
        } catch {
            setError('No se pudo actualizar la lista. Vuelve a intentarlo.');
        } finally {
            setCargando(false);
        }
    };

    const abrirExpediente = async (claimId: string) => {
        setAbriendo(true);
        setError(null);
        try {
            const respuesta = await AdminClaimServices.expediente(claimId);
            setExpediente(respuesta.data);
        } catch (e: unknown) {
            const respuesta = (e as { response?: { data?: { message?: string } } }).response;
            setError(respuesta?.data?.message ?? 'No se pudo abrir el expediente.');
        } finally {
            setAbriendo(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />

            <Breadcrumb aria-label="Ruta de navegación" className="mb-4">
                <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                    Inicio
                </BreadcrumbItem>
                <BreadcrumbItem>Reclamaciones</BreadcrumbItem>
            </Breadcrumb>

            <div className="mb-4">
                <h1 className="flex items-center gap-2 text-2xl font-black text-gray-900 dark:text-white">
                    <HiScale className="h-7 w-7 text-cyan-600" />
                    Reclamaciones y Expedientes
                </h1>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    El expediente reúne lo que la plataforma tiene guardado sobre una operación, para entregárselo a un comprador que quiere
                    denunciar.
                </p>
            </div>

            {error && (
                <div
                    data-testid="claims-error"
                    role="alert"
                    className="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
                >
                    {error}
                </div>
            )}

            <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <div className="flex items-center gap-3">
                        <HiClock className="h-8 w-8 text-amber-500" />
                        <div>
                            <p data-testid="metrica-abiertas" className="text-2xl font-black text-gray-900 dark:text-white">
                                {metricas.open_count}
                            </p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">abiertas, con el reloj corriendo</p>
                        </div>
                    </div>
                </Card>
                <Card>
                    <div className="flex items-center gap-3">
                        <HiExclamation className="h-8 w-8 text-red-500" />
                        <div>
                            <p data-testid="metrica-silencios" className="text-2xl font-black text-gray-900 dark:text-white">
                                {metricas.timeout_count}
                            </p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">resueltas por silencio de la tienda</p>
                        </div>
                    </div>
                </Card>
                <Card>
                    <div className="flex items-center gap-3">
                        <HiScale className="h-8 w-8 text-cyan-500" />
                        <div>
                            <p className="text-2xl font-black text-gray-900 dark:text-white">{metricas.total_count}</p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">en total</p>
                        </div>
                    </div>
                </Card>
                {/*
                 * El techo mensual de alarma. `platform_covered_amount` se escribía en cada
                 * reclamación desde la fase B y nadie lo sumaba nunca: el mes se podía
                 * descontrolar entero sin que hubiera dónde verlo.
                 *
                 * Superarlo NO corta ningún pago. Por eso la tarjeta cambia de color pero no
                 * bloquea nada: lo que pide es que alguien mire la causa.
                 */}
                <Card>
                    <div className="flex items-center gap-3">
                        <HiCurrencyDollar className={`h-8 w-8 ${metricas.coverage_month?.over ? 'text-red-500' : 'text-emerald-500'}`} />
                        <div>
                            <p
                                data-testid="metrica-cobertura"
                                className={`text-2xl font-black ${
                                    metricas.coverage_month?.over ? 'text-red-600 dark:text-red-500' : 'text-gray-900 dark:text-white'
                                }`}
                            >
                                ${(metricas.coverage_month?.spent_usd ?? 0).toFixed(2)}
                            </p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                {metricas.coverage_month?.over
                                    ? `puestos este mes — pasó del techo de $${(metricas.coverage_month?.threshold_usd ?? 0).toFixed(0)}`
                                    : `puestos este mes, de $${(metricas.coverage_month?.threshold_usd ?? 0).toFixed(0)}`}
                            </p>
                        </div>
                    </div>
                </Card>
            </div>

            <Card>
                <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div className="flex-1">
                        <Label htmlFor="claims-busqueda">Buscar</Label>
                        <TextInput
                            id="claims-busqueda"
                            icon={HiSearch}
                            placeholder="Número de pedido, producto o correo del comprador"
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') void recargar(1);
                            }}
                        />
                    </div>
                    <div className="sm:w-56">
                        <Label htmlFor="claims-estado">Estado</Label>
                        <Select
                            id="claims-estado"
                            value={filtroEstado}
                            onChange={(e) => {
                                setFiltroEstado(e.target.value);
                                void recargar(1, e.target.value);
                            }}
                        >
                            <option value="open">Abiertas</option>
                            <option value="approved">Aprobadas</option>
                            <option value="rejected">Rechazadas</option>
                            <option value="all">Todas</option>
                        </Select>
                    </div>
                    <Button color="light" onClick={() => void recargar(paginacion.current_page)} disabled={cargando}>
                        <HiRefresh className={`mr-2 h-4 w-4 ${cargando ? 'animate-spin' : ''}`} />
                        Actualizar
                    </Button>
                </div>

                {reclamaciones.length === 0 ? (
                    <div data-testid="claims-vacio" className="py-10 text-center">
                        <HiInformationCircle className="mx-auto h-10 w-10 text-gray-300" />
                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            {filtroEstado === 'open'
                                ? 'No hay reclamaciones abiertas.'
                                : 'Aquí aparecerán las reclamaciones cuando un comprador abra una.'}
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead>
                                <TableRow>
                                    <TableHeadCell>Pedido</TableHeadCell>
                                    <TableHeadCell>Tienda</TableHeadCell>
                                    <TableHeadCell>Importe</TableHeadCell>
                                    <TableHeadCell>Abierta</TableHeadCell>
                                    <TableHeadCell>Estado</TableHeadCell>
                                    <TableHeadCell>
                                        <span className="sr-only">Expediente</span>
                                    </TableHeadCell>
                                </TableRow>
                            </TableHead>
                            <TableBody className="divide-y">
                                {reclamaciones.map((r) => {
                                    const etiqueta = ESTADO[r.status] ?? { texto: r.status, color: 'gray' };

                                    return (
                                        <TableRow key={r.id} data-testid={`claim-${r.id}`}>
                                            <TableCell className="font-semibold text-gray-900 dark:text-white">
                                                {r.order_number}
                                                <div className="text-xs font-normal text-gray-400">{r.product_name}</div>
                                            </TableCell>
                                            <TableCell className="text-xs">{r.tenant_name ?? r.tenant_id}</TableCell>
                                            <TableCell className="text-xs font-bold">{dinero(r.amount)}</TableCell>
                                            <TableCell className="text-xs">{fecha(r.created_at)}</TableCell>
                                            <TableCell>
                                                <Badge color={etiqueta.color} className="w-fit">
                                                    {etiqueta.texto}
                                                </Badge>
                                                {r.resolved_by === 'timeout' && (
                                                    <div
                                                        data-testid={`silencio-${r.id}`}
                                                        className="mt-1 text-[10px] font-bold text-red-600 uppercase dark:text-red-400"
                                                    >
                                                        por silencio
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Button size="xs" color="light" onClick={() => void abrirExpediente(r.id)}>
                                                    Ver expediente
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {paginacion.last_page > 1 && (
                    <div className="mt-4 flex items-center justify-between text-sm">
                        <span className="text-gray-500 dark:text-gray-400">
                            Página {paginacion.current_page} de {paginacion.last_page} · {paginacion.total} reclamaciones
                        </span>
                        <div className="flex gap-2">
                            <Button
                                size="xs"
                                color="light"
                                disabled={paginacion.current_page <= 1}
                                onClick={() => void recargar(paginacion.current_page - 1)}
                            >
                                Anterior
                            </Button>
                            <Button
                                size="xs"
                                color="light"
                                disabled={paginacion.current_page >= paginacion.last_page}
                                onClick={() => void recargar(paginacion.current_page + 1)}
                            >
                                Siguiente
                            </Button>
                        </div>
                    </div>
                )}
            </Card>

            <Modal show={abriendo || expediente !== null} onClose={() => setExpediente(null)} size="3xl">
                <ModalHeader>Expediente de reclamación</ModalHeader>
                <ModalBody>
                    {abriendo || expediente === null ? (
                        <div className="flex justify-center py-10">
                            <Spinner aria-label="Abriendo expediente" />
                        </div>
                    ) : (
                        <div className="space-y-5 text-sm" data-testid="expediente">
                            <section>
                                <h3 className="mb-2 text-xs font-black tracking-wide text-gray-400 uppercase">La reclamación</h3>
                                <dl className="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                    <Dato k="Pedido" v={expediente.claim.order_number} />
                                    <Dato k="Producto" v={expediente.claim.product_name} />
                                    <Dato k="Importe" v={dinero(expediente.claim.amount)} />
                                    <Dato k="Motivo" v={expediente.claim.reason} />
                                    <Dato k="Cubierto por la plataforma" v={dinero(expediente.claim.platform_covered_amount)} />
                                    <Dato k="Cómo se resolvió" v={comoSeResolvio(expediente.claim.resolved_by)} />
                                </dl>
                                {expediente.claim.description && (
                                    <p className="mt-2 rounded-xl bg-gray-50 p-3 text-xs italic dark:bg-gray-800">«{expediente.claim.description}»</p>
                                )}
                                {expediente.claim.resolution_notes && (
                                    <p className="mt-2 rounded-xl bg-gray-50 p-3 text-xs dark:bg-gray-800">
                                        <strong>Respuesta de la tienda:</strong> {expediente.claim.resolution_notes}
                                    </p>
                                )}
                            </section>

                            {/*
                             * La cronologia es la mitad del expediente: es lo que sostiene un
                             * caso. Se presenta como secuencia y no como una tabla de campos
                             * para que se lea en el orden en que ocurrio.
                             */}
                            <section data-testid="cronologia">
                                <h3 className="mb-2 text-xs font-black tracking-wide text-gray-400 uppercase">Cronología</h3>
                                <ol className="space-y-2 border-l-2 border-gray-200 pl-4 dark:border-gray-700">
                                    <li>
                                        <span className="font-bold">{fecha(expediente.claim.delivered_at)}</span> — Entrega registrada
                                    </li>
                                    <li>
                                        <span className="font-bold">{fecha(expediente.claim.claimed_at)}</span> — El comprador presenta la reclamación
                                    </li>
                                    <li>
                                        <span className="font-bold">{fecha(expediente.claim.resolved_at)}</span> — Resolución
                                    </li>
                                </ol>
                            </section>

                            <section>
                                <h3 className="mb-2 text-xs font-black tracking-wide text-gray-400 uppercase">El comprador</h3>
                                <dl className="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                    <Dato k="Nombre" v={expediente.customer.name} />
                                    <Dato k="Documento" v={expediente.customer.document_id} />
                                    <Dato k="Correo" v={expediente.customer.email} />
                                    <Dato k="Teléfono" v={expediente.customer.phone} />
                                </dl>
                            </section>

                            <section data-testid="identidad-tienda">
                                <h3 className="mb-2 text-xs font-black tracking-wide text-gray-400 uppercase">La tienda</h3>
                                <dl className="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                    <Dato k="Razón social" v={expediente.store.legal_name} />
                                    <Dato k="Documento" v={expediente.store.cedula} />
                                    <Dato k="RIF" v={expediente.store.rif} />
                                    <Dato k="Teléfono" v={expediente.store.phone} />
                                    <Dato k="Dirección" v={expediente.store.address} />
                                    <Dato k="Identidad verificada" v={expediente.store.kyc_status === 'verified' ? 'Sí' : 'No'} />
                                    <Dato k="Nivel de reputación" v={expediente.store.reputation.level} />
                                    <Dato k="Reclamaciones sin responder" v={String(expediente.store.reputation.unanswered_claims)} />
                                </dl>
                                <p className="mt-2 text-[11px] text-gray-400">
                                    Estos datos de identidad salen en el expediente por una decisión de la fase de desarrollo, pendiente de revisión
                                    legal.
                                </p>
                            </section>

                            <section>
                                <h3 className="mb-2 text-xs font-black tracking-wide text-gray-400 uppercase">Evidencias de entrega</h3>
                                {expediente.delivery === null ? (
                                    <p className="text-xs text-gray-500">No consta ningún registro de entrega para este pedido.</p>
                                ) : (
                                    <dl className="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                        <Dato k="Entrega declarada" v={fecha(expediente.delivery.declared_delivered_at)} />
                                        <Dato k="Confirmada" v={fecha(expediente.delivery.confirmed_at)} />
                                        <Dato
                                            k="Liberada por"
                                            v={expediente.delivery.released_by === 'timeout' ? 'Vencimiento del plazo' : 'Confirmación del comprador'}
                                        />
                                        <Dato
                                            k="Pruebas aportadas"
                                            v={`${expediente.delivery.shipment_evidence.length} de la tienda, ${expediente.delivery.confirmation_evidence.length} del comprador`}
                                        />
                                    </dl>
                                )}
                            </section>
                        </div>
                    )}
                </ModalBody>
                <ModalFooter>
                    {expediente && (
                        <Button as="a" href={AdminClaimServices.urlDelPdf(expediente.claim.id)} color="blue" size="sm">
                            <HiDocumentDownload className="mr-2 h-4 w-4" />
                            Descargar PDF
                        </Button>
                    )}
                    <Button color="light" size="sm" onClick={() => setExpediente(null)}>
                        Cerrar
                    </Button>
                </ModalFooter>
            </Modal>
        </Dashboard>
    );
};

/** Un par etiqueta/valor. Existe para no repetir el mismo `dt`/`dd` veinte veces. */
const Dato: FC<{ k: string; v: string | null | undefined }> = ({ k, v }) => (
    <div className="flex gap-2">
        <dt className="w-44 shrink-0 text-gray-500 dark:text-gray-400">{k}</dt>
        <dd className="font-semibold text-gray-900 dark:text-white">{v || '—'}</dd>
    </div>
);

export default AdminClaimDossierPage;
