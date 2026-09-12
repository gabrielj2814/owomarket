import Dashboard from '@/components/layouts/Dashboard';
import AdminKycServices, {
    KycIdentityMatch,
    KycListResult,
    KycMetrics,
    KycPagination,
    KycProfileRow,
} from '@/Services/AdminKycServices';
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
    Textarea,
    TextInput,
} from 'flowbite-react';
import React, { FC, useState } from 'react';
import {
    HiBadgeCheck,
    HiClock,
    HiHome,
    HiIdentification,
    HiInformationCircle,
    HiRefresh,
    HiSearch,
    HiXCircle,
} from 'react-icons/hi';

/**
 * Revisión de identidad de comerciantes (subsistema 1, vista 1 de
 * `planes/por_hacer/PLAN_VISTAS_PENDIENTES.md`).
 *
 * **Esta pantalla es la que deja cobrar a las tiendas.** El KYC exige identidad verificada para
 * solicitar un retiro, y hasta que existió no había ninguna forma de verificar a nadie: todos
 * los expedientes se quedaban en `pending` y el cobro estaba bloqueado para toda la plataforma.
 *
 * Tres decisiones que conviene no deshacer sin pensarlo:
 *
 * 1. **La cédula y el RIF no aparecen.** Están cifrados en reposo y el backend no los manda.
 *    Si algún día la revisión necesita verlos, eso es un endpoint aparte con su propio registro
 *    de acceso, no una columna más en la tabla.
 * 2. **Rechazar exige motivo**, y el motivo se pide en el mismo sitio donde se pulsa. Sin él, el
 *    comerciante ve su cobro bloqueado sin saber qué corregir.
 * 3. **Las tiendas que comparten identidad se avisan, no se acusan.** Un dueño con dos negocios
 *    es normal; el texto tiene que decirlo para que nadie rechace por reflejo.
 */
interface AdminKycReviewPageProps {
    title?: string;
    user_id: string;
    profiles: KycProfileRow[];
    pagination: KycPagination;
    metrics: KycMetrics;
    filters?: { status?: string | null; search?: string | null };
}

const ETIQUETA_ESTADO: Record<string, { texto: string; color: string }> = {
    pending: { texto: 'Pendiente', color: 'warning' },
    verified: { texto: 'Verificada', color: 'success' },
    rejected: { texto: 'Rechazada', color: 'failure' },
};

const fecha = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString('es-VE') : '—');

/** Cuántos días lleva esperando. Es el dato que ordena la lista, así que conviene enseñarlo. */
const diasEsperando = (iso: string | null): number | null => {
    if (!iso) return null;
    const ms = Date.now() - new Date(iso).getTime();
    return Math.max(0, Math.floor(ms / 86_400_000));
};

const AdminKycReviewPage: FC<AdminKycReviewPageProps> = ({
    title = 'Verificación de Identidad de Comerciantes - OwOMarket Admin',
    user_id,
    profiles: perfilesIniciales = [],
    pagination: paginacionInicial,
    metrics: metricasIniciales,
    filters,
}) => {
    const [perfiles, setPerfiles] = useState<KycProfileRow[]>(perfilesIniciales);
    const [paginacion, setPaginacion] = useState<KycPagination>(paginacionInicial);
    const [metricas, setMetricas] = useState<KycMetrics>(metricasIniciales);
    const [cargando, setCargando] = useState(false);

    const [filtroEstado, setFiltroEstado] = useState(filters?.status ?? 'pending');
    const [busqueda, setBusqueda] = useState(filters?.search ?? '');

    // El expediente abierto en el panel de revisión.
    const [revisando, setRevisando] = useState<KycProfileRow | null>(null);
    const [rechazando, setRechazando] = useState(false);
    const [motivo, setMotivo] = useState('');
    const [errorMotivo, setErrorMotivo] = useState<string | null>(null);
    const [enviando, setEnviando] = useState(false);

    const [coincidencias, setCoincidencias] = useState<KycIdentityMatch[] | null>(null);
    const [buscandoIdentidad, setBuscandoIdentidad] = useState(false);

    const [aviso, setAviso] = useState<{ tipo: 'ok' | 'error'; texto: string } | null>(null);

    const recargar = async (pagina = 1, estado = filtroEstado, texto = busqueda) => {
        setCargando(true);
        try {
            const respuesta = await AdminKycServices.listar({
                status: estado,
                search: texto || undefined,
                page: pagina,
            });
            const datos = respuesta.data as KycListResult;
            setPerfiles(datos.profiles);
            setPaginacion(datos.pagination);
            setMetricas(datos.metrics);
        } catch {
            setAviso({ tipo: 'error', texto: 'No se pudo actualizar la lista. Vuelve a intentarlo.' });
        } finally {
            setCargando(false);
        }
    };

    const abrirRevision = async (perfil: KycProfileRow, paraRechazar: boolean) => {
        setRevisando(perfil);
        setRechazando(paraRechazar);
        setMotivo('');
        setErrorMotivo(null);
        setCoincidencias(null);

        // La comprobación de identidad se lanza al abrir el expediente, no a petición: es el
        // motivo por el que el KYC existe, y un aviso que hay que ir a buscar no se mira.
        setBuscandoIdentidad(true);
        try {
            const respuesta = await AdminKycServices.coincidenciasDeIdentidad(perfil.id);
            setCoincidencias(respuesta.data ?? []);
        } catch {
            setCoincidencias([]);
        } finally {
            setBuscandoIdentidad(false);
        }
    };

    const cerrarRevision = () => {
        setRevisando(null);
        setMotivo('');
        setErrorMotivo(null);
        setCoincidencias(null);
    };

    const confirmar = async () => {
        if (!revisando) return;

        if (rechazando && motivo.trim() === '') {
            // Se comprueba aquí además de en el backend para poder señalar el campo en vez de
            // soltar un toast genérico que no dice dónde está el problema.
            setErrorMotivo('Indica el motivo del rechazo: el comerciante necesita saber qué corregir.');
            return;
        }

        setEnviando(true);
        setErrorMotivo(null);
        try {
            const respuesta = await AdminKycServices.revisar(revisando.id, {
                approved: !rechazando,
                reason: rechazando ? motivo.trim() : undefined,
            });

            setAviso({ tipo: 'ok', texto: respuesta.message });
            cerrarRevision();
            await recargar(paginacion.current_page);
        } catch (error: unknown) {
            const respuesta = (error as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } })
                .response;

            if (respuesta?.status === 422) {
                setErrorMotivo(respuesta.data?.errors?.reason?.[0] ?? respuesta.data?.message ?? 'Revisa el motivo.');
            } else {
                setAviso({
                    tipo: 'error',
                    texto: respuesta?.data?.message ?? 'No se pudo registrar la revisión. Vuelve a intentarlo.',
                });
                cerrarRevision();
            }
        } finally {
            setEnviando(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />

            <Breadcrumb aria-label="Ruta de navegación" className="mb-4">
                <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                    Inicio
                </BreadcrumbItem>
                <BreadcrumbItem>Verificación de Identidad</BreadcrumbItem>
            </Breadcrumb>

            <div className="mb-4">
                <h1 className="flex items-center gap-2 text-2xl font-black text-gray-900 dark:text-white">
                    <HiIdentification className="h-7 w-7 text-cyan-600" />
                    Verificación de Identidad
                </h1>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Una tienda sin identidad verificada <strong>no puede solicitar retiros</strong>. Cada expediente
                    pendiente es dinero esperando.
                </p>
            </div>

            {aviso && (
                <div
                    data-testid="kyc-aviso"
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

            <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <Card>
                    <div className="flex items-center gap-3">
                        <HiClock className="h-8 w-8 text-amber-500" />
                        <div>
                            <p className="text-2xl font-black text-gray-900 dark:text-white" data-testid="metrica-pendientes">
                                {metricas.pending_count}
                            </p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                {metricas.pending_count === 1 ? 'tienda no puede cobrar' : 'tiendas no pueden cobrar'}
                            </p>
                        </div>
                    </div>
                </Card>
                <Card>
                    <div className="flex items-center gap-3">
                        <HiBadgeCheck className="h-8 w-8 text-emerald-500" />
                        <div>
                            <p className="text-2xl font-black text-gray-900 dark:text-white">{metricas.verified_count}</p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">verificadas</p>
                        </div>
                    </div>
                </Card>
                <Card>
                    <div className="flex items-center gap-3">
                        <HiXCircle className="h-8 w-8 text-red-500" />
                        <div>
                            <p className="text-2xl font-black text-gray-900 dark:text-white">{metricas.rejected_count}</p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">rechazadas</p>
                        </div>
                    </div>
                </Card>
            </div>

            <Card>
                <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div className="flex-1">
                        <Label htmlFor="kyc-busqueda">Buscar</Label>
                        <TextInput
                            id="kyc-busqueda"
                            icon={HiSearch}
                            placeholder="Nombre legal, teléfono o tienda"
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') void recargar(1);
                            }}
                        />
                    </div>
                    <div className="sm:w-56">
                        <Label htmlFor="kyc-estado">Estado</Label>
                        <Select
                            id="kyc-estado"
                            value={filtroEstado}
                            onChange={(e) => {
                                setFiltroEstado(e.target.value);
                                void recargar(1, e.target.value);
                            }}
                        >
                            <option value="pending">Pendientes</option>
                            <option value="verified">Verificadas</option>
                            <option value="rejected">Rechazadas</option>
                            <option value="all">Todas</option>
                        </Select>
                    </div>
                    <Button color="light" onClick={() => void recargar(paginacion.current_page)} disabled={cargando}>
                        <HiRefresh className={`mr-2 h-4 w-4 ${cargando ? 'animate-spin' : ''}`} />
                        Actualizar
                    </Button>
                </div>

                {cargando && perfiles.length === 0 ? (
                    <div className="flex justify-center py-10">
                        <Spinner aria-label="Cargando expedientes" />
                    </div>
                ) : perfiles.length === 0 ? (
                    <div data-testid="kyc-vacio" className="py-10 text-center">
                        <HiInformationCircle className="mx-auto h-10 w-10 text-gray-300" />
                        {filtroEstado === 'pending' ? (
                            <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                No hay verificaciones pendientes.
                            </p>
                        ) : (
                            <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Aquí aparecerán los expedientes cuando una tienda envíe sus datos de identidad desde su
                                billetera.
                            </p>
                        )}
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead>
                                <TableRow>
                                    <TableHeadCell>Tienda</TableHeadCell>
                                    <TableHeadCell>Responsable</TableHeadCell>
                                    <TableHeadCell>Contacto</TableHeadCell>
                                    <TableHeadCell>Enviado</TableHeadCell>
                                    <TableHeadCell>Estado</TableHeadCell>
                                    <TableHeadCell>
                                        <span className="sr-only">Acciones</span>
                                    </TableHeadCell>
                                </TableRow>
                            </TableHead>
                            <TableBody className="divide-y">
                                {perfiles.map((perfil) => {
                                    const dias = diasEsperando(perfil.created_at);
                                    const etiqueta = ETIQUETA_ESTADO[perfil.status] ?? {
                                        texto: perfil.status,
                                        color: 'gray',
                                    };

                                    return (
                                        <TableRow key={perfil.id} data-testid={`kyc-fila-${perfil.id}`}>
                                            <TableCell className="font-semibold text-gray-900 dark:text-white">
                                                {perfil.tenant_name ?? '—'}
                                            </TableCell>
                                            <TableCell>{perfil.legal_name}</TableCell>
                                            <TableCell className="text-xs">
                                                <div>{perfil.phone ?? '—'}</div>
                                                <div className="text-gray-400">{perfil.address ?? '—'}</div>
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                {fecha(perfil.created_at)}
                                                {perfil.status === 'pending' && dias !== null && (
                                                    <div className="text-amber-600 dark:text-amber-400">
                                                        {dias === 0
                                                            ? 'hoy'
                                                            : `${dias} ${dias === 1 ? 'día' : 'días'} esperando`}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge color={etiqueta.color} className="w-fit">
                                                    {etiqueta.texto}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {perfil.status === 'pending' ? (
                                                    <div className="flex gap-2">
                                                        <Button
                                                            size="xs"
                                                            color="green"
                                                            onClick={() => void abrirRevision(perfil, false)}
                                                        >
                                                            Verificar
                                                        </Button>
                                                        <Button
                                                            size="xs"
                                                            color="red"
                                                            onClick={() => void abrirRevision(perfil, true)}
                                                        >
                                                            Rechazar
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-gray-400">
                                                        Revisado el {fecha(perfil.reviewed_at)}
                                                    </span>
                                                )}
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
                            Página {paginacion.current_page} de {paginacion.last_page} · {paginacion.total} expedientes
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

            <Modal show={revisando !== null} onClose={cerrarRevision} size="lg">
                <ModalHeader>{rechazando ? 'Rechazar expediente' : 'Verificar identidad'}</ModalHeader>
                <ModalBody>
                    {revisando && (
                        <div className="space-y-4 text-sm">
                            <div className="rounded-xl bg-gray-50 p-3 dark:bg-gray-800">
                                <p className="font-bold text-gray-900 dark:text-white">{revisando.tenant_name}</p>
                                <p className="text-gray-600 dark:text-gray-400">{revisando.legal_name}</p>
                                <p className="text-xs text-gray-500">
                                    {revisando.phone} · {revisando.address}
                                </p>
                                <p className="mt-2 text-xs text-gray-500">
                                    {revisando.has_document
                                        ? 'El comerciante adjuntó foto del documento.'
                                        : 'Sin foto del documento (es opcional).'}
                                </p>
                            </div>

                            <div data-testid="kyc-identidad">
                                {buscandoIdentidad ? (
                                    <p className="text-xs text-gray-400">Comprobando si esta identidad ya tiene tiendas…</p>
                                ) : coincidencias && coincidencias.length > 0 ? (
                                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/40">
                                        <p className="font-bold text-amber-800 dark:text-amber-300">
                                            Esta identidad ya figura en{' '}
                                            {coincidencias.length === 1 ? 'otra tienda' : `otras ${coincidencias.length} tiendas`}.
                                        </p>
                                        <ul className="mt-2 space-y-1 text-xs text-amber-800 dark:text-amber-300">
                                            {coincidencias.map((otra) => (
                                                <li key={otra.tenant_id}>
                                                    <strong>{otra.tenant_name ?? otra.tenant_id}</strong> — tienda{' '}
                                                    {otra.tenant_status}, verificación {otra.kyc_status} · desde{' '}
                                                    {fecha(otra.created_at)}
                                                </li>
                                            ))}
                                        </ul>
                                        <p className="mt-2 text-xs text-amber-700 dark:text-amber-400">
                                            Tener más de una tienda <strong>no es una falta</strong>: un mismo dueño puede
                                            llevar varios negocios. Este aviso es contexto para decidir, no un motivo de
                                            rechazo por sí solo.
                                        </p>
                                    </div>
                                ) : (
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        Esta identidad no figura en ninguna otra tienda.
                                    </p>
                                )}
                            </div>

                            {rechazando ? (
                                <div>
                                    <Label htmlFor="kyc-motivo">Motivo del rechazo</Label>
                                    <Textarea
                                        id="kyc-motivo"
                                        rows={3}
                                        value={motivo}
                                        color={errorMotivo ? 'failure' : undefined}
                                        onChange={(e) => {
                                            setMotivo(e.target.value);
                                            setErrorMotivo(null);
                                        }}
                                        placeholder="Ej.: la foto del documento está ilegible."
                                    />
                                    {errorMotivo && (
                                        <p role="alert" className="mt-1 text-xs font-semibold text-red-600">
                                            {errorMotivo}
                                        </p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500">
                                        El comerciante verá este texto en su billetera. Es lo único que tendrá para
                                        corregir el envío.
                                    </p>
                                </div>
                            ) : (
                                <p className="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    Al verificar, la tienda <strong>podrá solicitar retiros</strong> de inmediato.
                                </p>
                            )}
                        </div>
                    )}
                </ModalBody>
                <ModalFooter>
                    <Button
                        // Esta version de Flowbite NO tiene los colores `success`/`failure`
                        // en botones --solo en insignias--: usarlos deja el boton sin relleno,
                        // y el primario acaba pareciendo menos importante que «Cancelar».
                        color={rechazando ? 'red' : 'green'}
                        onClick={() => void confirmar()}
                        disabled={enviando}
                    >
                        {enviando && <Spinner size="sm" className="mr-2" />}
                        {rechazando ? 'Rechazar' : 'Verificar'}
                    </Button>
                    <Button color="light" onClick={cerrarRevision} disabled={enviando}>
                        Cancelar
                    </Button>
                </ModalFooter>
            </Modal>
        </Dashboard>
    );
};

export default AdminKycReviewPage;
