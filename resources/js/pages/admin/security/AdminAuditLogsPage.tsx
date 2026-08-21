import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import axios from "axios";
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
    Select,
    Spinner,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
    TextInput,
} from "flowbite-react";
import React, { FC, useState } from "react";
import {
    HiClock,
    HiCode,
    HiEye,
    HiFilter,
    HiHome,
    HiRefresh,
    HiSearch,
    HiShieldCheck,
} from "react-icons/hi";
import { LuActivity, LuFingerprint, LuShieldAlert } from "react-icons/lu";

interface AuditLog {
    id: string;
    user_id?: string | null;
    user_name?: string | null;
    user_email?: string | null;
    action: string;
    entity_type?: string | null;
    entity_id?: string | null;
    ip_address?: string | null;
    user_agent?: string | null;
    old_values?: any;
    new_values?: any;
    description?: string | null;
    created_at: string;
}

interface Metrics {
    total_logs: number;
    security_actions: number;
    financial_actions: number;
}

interface PaginationData {
    data: AuditLog[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface AdminAuditLogsPageProps {
    title?: string;
    user_id: string;
    logs_data: PaginationData;
    metrics: Metrics;
    actions_list: string[];
    filters: {
        action: string;
        entity_type: string;
        search: string;
    };
}

const AdminAuditLogsPage: FC<AdminAuditLogsPageProps> = ({
    title = "Pista de Auditoría y Seguridad - OwOMarket",
    user_id,
    logs_data: initialPagination,
    metrics: initialMetrics,
    actions_list: initialActions,
    filters: initialFilters,
}) => {
    const [logs, setLogs] = useState<AuditLog[]>(initialPagination.data || []);
    const [pagination, setPagination] = useState({
        current_page: initialPagination.current_page || 1,
        last_page: initialPagination.last_page || 1,
        total: initialPagination.total || 0,
        per_page: initialPagination.per_page || 20,
    });
    const [metrics, setMetrics] = useState<Metrics>(initialMetrics);

    const [search, setSearch] = useState(initialFilters.search || "");
    const [actionFilter, setActionFilter] = useState(initialFilters.action || "");
    const [loading, setLoading] = useState(false);
    const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null);

    // Modal Detalle JSON
    const [detailModalOpen, setDetailModalOpen] = useState(false);
    const [selectedLog, setSelectedLog] = useState<AuditLog | null>(null);

    const fetchLogs = async (page = 1) => {
        setLoading(true);
        try {
            const params: any = { page };
            if (search.trim()) params.search = search.trim();
            if (actionFilter) params.action = actionFilter;

            const response = await axios.get("/admin/api/security/audit-logs", { params });
            if (response.data?.status === "success") {
                const resData = response.data.data;
                setLogs(resData.logs.data);
                setPagination({
                    current_page: resData.logs.current_page,
                    last_page: resData.logs.last_page,
                    total: resData.logs.total,
                    per_page: resData.logs.per_page,
                });
                setMetrics(resData.metrics);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al cargar registros de auditoría." });
        } finally {
            setLoading(false);
        }
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchLogs(1);
    };

    const handleOpenDetail = (log: AuditLog) => {
        setSelectedLog(log);
        setDetailModalOpen(true);
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />
            <div className="p-4 sm:p-6 space-y-6 max-w-7xl mx-auto">
                {/* Header & Breadcrumbs */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <Breadcrumb className="mb-2">
                            <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                                Panel Global
                            </BreadcrumbItem>
                            <BreadcrumbItem>Seguridad & Staff</BreadcrumbItem>
                            <BreadcrumbItem>Pista de Auditoría</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <LuFingerprint className="text-blue-600 w-8 h-8" />
                            Pista de Auditoría y Seguridad
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Historial cronológico inmutable de acciones administrativas, financieras y cambios de gobernanza.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button color="light" size="sm" onClick={() => fetchLogs(pagination.current_page)} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-1.5 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                    </div>
                </div>

                {/* Toast */}
                {toast && (
                    <div
                        className={`p-4 rounded-lg flex items-center justify-between text-sm ${
                            toast.type === "success"
                                ? "bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-300 border border-green-200 dark:border-green-800"
                                : "bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800"
                        }`}
                    >
                        <span>{toast.text}</span>
                        <button onClick={() => setToast(null)} className="font-bold text-lg leading-none ml-4">
                            &times;
                        </button>
                    </div>
                )}

                {/* KPI CARDS */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Card className="border-l-4 border-blue-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Total Eventos Registrados
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_logs || 0}
                                </h3>
                                <p className="text-xs text-blue-600 font-medium mt-1">
                                    Pista completa
                                </p>
                            </div>
                            <div className="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-xl">
                                <LuActivity className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-indigo-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Acciones de Seguridad / Roles
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.security_actions || 0}
                                </h3>
                                <p className="text-xs text-indigo-600 font-medium mt-1">
                                    Cambios de permisos & estado
                                </p>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-xl">
                                <HiShieldCheck className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Operaciones Financieras
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.financial_actions || 0}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    Liquidaciones & reembolsos
                                </p>
                            </div>
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-xl">
                                <LuFingerprint className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* FILTROS Y TABLA DE AUDITORIA */}
                <Card className="shadow-sm">
                    <form onSubmit={handleSearchSubmit} className="flex flex-col md:flex-row items-center gap-3 mb-4">
                        <div className="relative flex-1 w-full">
                            <TextInput
                                icon={HiSearch}
                                placeholder="Buscar por usuario, email, descripción o ID..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        <div className="w-full md:w-60">
                            <Select
                                value={actionFilter}
                                onChange={(e) => setActionFilter(e.target.value)}
                            >
                                <option value="">Todas las Acciones</option>
                                {initialActions.map((act) => (
                                    <option key={act} value={act}>
                                        {act}
                                    </option>
                                ))}
                            </Select>
                        </div>
                        <Button type="submit" color="blue" disabled={loading} className="w-full md:w-auto">
                            <HiFilter className="w-4 h-4 mr-1.5" />
                            Filtrar
                        </Button>
                    </form>

                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                <TableHeadCell>Acción</TableHeadCell>
                                <TableHeadCell>Descripción</TableHeadCell>
                                <TableHeadCell>Usuario / Actor</TableHeadCell>
                                <TableHeadCell>Entidad</TableHeadCell>
                                <TableHeadCell>IP / Dispositivo</TableHeadCell>
                                <TableHeadCell>Fecha & Hora</TableHeadCell>
                                <TableHeadCell className="text-right">Detalles</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y text-xs">
                                {logs.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-8 text-gray-400">
                                            No se registran eventos de auditoría con los filtros seleccionados.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    logs.map((log) => (
                                        <TableRow key={log.id}>
                                            <TableCell>
                                                <Badge
                                                    color={
                                                        log.action.includes("approved") ? "success" :
                                                        log.action.includes("rejected") || log.action.includes("suspend") ? "failure" :
                                                        log.action.includes("role") ? "purple" : "info"
                                                    }
                                                    className="font-mono text-[10px] w-fit"
                                                >
                                                    {log.action}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="max-w-xs font-medium text-gray-900 dark:text-white truncate" title={log.description || ""}>
                                                {log.description || "-"}
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-0.5">
                                                    <p className="font-bold text-gray-800 dark:text-gray-200">
                                                        {log.user_name || "Sistema"}
                                                    </p>
                                                    <p className="text-[10px] text-gray-400 font-mono">
                                                        {log.user_email || "-"}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-mono text-gray-500">
                                                {log.entity_type ? `${log.entity_type} (#${log.entity_id?.substring(0, 8)})` : "-"}
                                            </TableCell>
                                            <TableCell className="font-mono text-[11px] text-gray-400">
                                                {log.ip_address || "127.0.0.1"}
                                            </TableCell>
                                            <TableCell className="text-gray-500 text-[11px] whitespace-nowrap">
                                                {new Date(log.created_at).toLocaleString("es-VE")}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button size="xs" color="light" onClick={() => handleOpenDetail(log)}>
                                                    <HiCode className="w-3.5 h-3.5 mr-1 text-blue-600" />
                                                    Ver Diff
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {pagination.last_page > 1 && (
                        <div className="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Pagination
                                currentPage={pagination.current_page}
                                totalPages={pagination.last_page}
                                onPageChange={(page) => fetchLogs(page)}
                                showIcons
                                previousLabel="Anterior"
                                nextLabel="Siguiente"
                            />
                        </div>
                    )}
                </Card>

                {/* MODAL DETALLE DE AUDITORIA */}
                <Modal show={detailModalOpen} onClose={() => setDetailModalOpen(false)} size="lg">
                    <ModalHeader>
                        Detalle del Evento: {selectedLog?.action}
                    </ModalHeader>
                    <ModalBody className="space-y-4">
                        <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-xs space-y-1">
                            <p><strong>Actor:</strong> {selectedLog?.user_name} ({selectedLog?.user_email})</p>
                            <p><strong>Descripción:</strong> {selectedLog?.description}</p>
                            <p><strong>IP:</strong> {selectedLog?.ip_address} | <strong>Fecha:</strong> {selectedLog?.created_at ? new Date(selectedLog.created_at).toLocaleString("es-VE") : ""}</p>
                            {selectedLog?.user_agent && (
                                <p className="truncate text-gray-400 font-mono text-[10px]">
                                    <strong>User-Agent:</strong> {selectedLog.user_agent}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <h4 className="text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Valores Anteriores (Old State):
                                </h4>
                                <pre className="p-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg text-[11px] font-mono overflow-x-auto text-red-900 dark:text-red-200 max-h-48">
                                    {selectedLog?.old_values ? JSON.stringify(selectedLog.old_values, null, 2) : "null"}
                                </pre>
                            </div>
                            <div>
                                <h4 className="text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Valores Nuevos (New State):
                                </h4>
                                <pre className="p-3 bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-lg text-[11px] font-mono overflow-x-auto text-green-900 dark:text-green-200 max-h-48">
                                    {selectedLog?.new_values ? JSON.stringify(selectedLog.new_values, null, 2) : "null"}
                                </pre>
                            </div>
                        </div>
                    </ModalBody>
                    <ModalFooter>
                        <Button color="gray" onClick={() => setDetailModalOpen(false)}>
                            Cerrar
                        </Button>
                    </ModalFooter>
                </Modal>
            </div>
        </Dashboard>
    );
};

export default AdminAuditLogsPage;
