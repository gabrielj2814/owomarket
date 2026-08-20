import Dashboard from "@/components/layouts/Dashboard";
import { Head, Link } from "@inertiajs/react";
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
    Spinner,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
    Tabs,
    TabItem,
    TextInput,
} from "flowbite-react";
import React, { FC, useState } from "react";
import {
    HiArrowLeft,
    HiCheckCircle,
    HiClock,
    HiCurrencyDollar,
    HiExternalLink,
    HiHome,
    HiKey,
    HiLockClosed,
    HiOutlineExclamation,
    HiPencilAlt,
    HiRefresh,
    HiShoppingBag,
    HiTrash,
    HiUserCircle,
    HiUsers,
    HiXCircle,
} from "react-icons/hi";
import { LuBuilding2, LuLifeBuoy, LuReceipt, LuStore, LuUserCheck, LuWallet } from "react-icons/lu";
import { TbBuildingBank } from "react-icons/tb";

interface TenantOwner {
    id: string;
    name: string;
    email: string;
    phone?: string | null;
    avatar?: string | null;
    type?: string;
    is_active?: boolean;
}

interface DomainItem {
    id: string;
    domain: string;
}

interface TenantModel {
    id: string;
    name: string;
    slug: string;
    status: "active" | "inactive" | "suspended";
    request: "approved" | "rejected" | "in progress";
    timezone?: string;
    currency?: string;
    created_at: string;
    updated_at: string;
    domains?: DomainItem[];
    owners?: TenantOwner[];
    data?: {
        admin_notes?: string;
        governance_history?: Array<{
            action_by: string;
            status: string;
            request: string;
            reason: string;
            timestamp: string;
        }>;
    };
}

interface Metrics {
    total_sales_usd: number;
    total_orders_count: number;
    total_commissions_usd: number;
    products_published_count: number;
    total_payouts_settled_usd: number;
    total_payouts_pending_usd: number;
    open_tickets_count: number;
}

interface AdminTenantDetail360PageProps {
    title?: string;
    user_id: string;
    tenant: TenantModel;
    metrics: Metrics;
    recent_orders: any[];
    payouts: any[];
    tickets: any[];
}

const AdminTenantDetail360Page: FC<AdminTenantDetail360PageProps> = ({
    title = "Expediente 360° Tienda Inquilina - OwOMarket",
    user_id,
    tenant: initialTenant,
    metrics: initialMetrics,
    recent_orders: initialOrders = [],
    payouts: initialPayouts = [],
    tickets: initialTickets = [],
}) => {
    const [tenant, setTenant] = useState<TenantModel>(initialTenant);
    const [metrics, setMetrics] = useState<Metrics>(initialMetrics);
    const [orders, setOrders] = useState<any[]>(initialOrders);
    const [payouts, setPayouts] = useState<any[]>(initialPayouts);
    const [tickets, setTickets] = useState<any[]>(initialTickets);

    const [loadingSso, setLoadingSso] = useState(false);
    const [refreshing, setRefreshing] = useState(false);
    const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null);

    // Modal de Gobernanza (Cambiar Estado / Suspender / Aprobar)
    const [governanceModalOpen, setGovernanceModalOpen] = useState(false);
    const [selectedAction, setSelectedAction] = useState<"approve" | "suspend" | "activate" | "notes">("notes");
    const [actionReason, setActionReason] = useState("");
    const [adminNotes, setAdminNotes] = useState(tenant.data?.admin_notes || "");
    const [submittingGovernance, setSubmittingGovernance] = useState(false);

    const primaryDomain = tenant.domains?.[0]?.domain || `${tenant.slug}.owomarket.local`;

    const handleRefreshData = async () => {
        setRefreshing(true);
        try {
            const response = await axios.get(`/admin/api/tenants/${tenant.id}/360-data`);
            if (response.data?.status === "success") {
                setTenant(response.data.data.tenant);
                setMetrics(response.data.data.metrics);
                setOrders(response.data.data.recent_orders);
                setPayouts(response.data.data.payouts);
                setTickets(response.data.data.tickets);
                setToast({ type: "success", text: "Expediente 360° actualizado." });
            }
        } catch (e: any) {
            setToast({ type: "error", text: "Error al actualizar expediente." });
        } finally {
            setRefreshing(false);
        }
    };

    const handleImpersonateSSO = async () => {
        setLoadingSso(true);
        try {
            const response = await axios.post(`/admin/api/tenants/${tenant.id}/sso-token`, {
                admin_user_id: user_id,
            });

            if (response.data?.status === "success" && response.data.data.sso_url) {
                window.open(response.data.data.sso_url, "_blank", "noopener,noreferrer");
                setToast({
                    type: "success",
                    text: `Accediendo al backoffice de ${tenant.name} en una nueva pestaña...`,
                });
            } else {
                throw new Error("No se pudo generar la sesión SSO.");
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al generar acceso de soporte SSO.",
            });
        } finally {
            setLoadingSso(false);
        }
    };

    const handleGovernanceSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmittingGovernance(true);

        const payload: any = {
            reason: actionReason.trim() || undefined,
            admin_notes: adminNotes.trim(),
        };

        if (selectedAction === "approve") {
            payload.request = "approved";
            payload.status = "active";
        } else if (selectedAction === "suspend") {
            payload.status = "suspended";
        } else if (selectedAction === "activate") {
            payload.status = "active";
            payload.request = "approved";
        }

        try {
            const response = await axios.patch(`/admin/api/tenants/${tenant.id}/governance-status`, payload);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Estado de la tienda ${tenant.name} actualizado exitosamente.`,
                });
                setGovernanceModalOpen(false);
                setActionReason("");
                handleRefreshData();
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al actualizar gobernanza del comercio.",
            });
        } finally {
            setSubmittingGovernance(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />
            <div className="p-4 sm:p-6 space-y-6 max-w-7xl mx-auto">
                {/* Navegación y Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <Breadcrumb className="mb-2">
                            <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                                Panel Global
                            </BreadcrumbItem>
                            <BreadcrumbItem href={`/admin/backoffice/${user_id}/module`}>
                                Tiendas Inquilinas
                            </BreadcrumbItem>
                            <BreadcrumbItem>{tenant.name}</BreadcrumbItem>
                        </Breadcrumb>
                        <div className="flex items-center gap-3">
                            <Link
                                href={`/admin/backoffice/${user_id}/module`}
                                className="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 transition"
                            >
                                <HiArrowLeft className="w-5 h-5" />
                            </Link>
                            <div>
                                <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                                    <LuStore className="text-indigo-600 w-8 h-8" />
                                    {tenant.name}
                                </h1>
                                <p className="text-xs sm:text-sm text-gray-500 font-mono mt-0.5">
                                    ID: {tenant.id} • Creado el {new Date(tenant.created_at).toLocaleDateString("es-VE")}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                        <Button
                            color="light"
                            size="sm"
                            onClick={handleRefreshData}
                            disabled={refreshing}
                        >
                            <HiRefresh className={`w-4 h-4 mr-1.5 ${refreshing ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>

                        <Button
                            color="blue"
                            size="sm"
                            onClick={handleImpersonateSSO}
                            disabled={loadingSso}
                            className="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 font-bold shadow"
                        >
                            {loadingSso ? (
                                <Spinner size="sm" className="mr-2" />
                            ) : (
                                <HiKey className="w-4 h-4 mr-2" />
                            )}
                            Acceder al Backoffice (SSO)
                        </Button>
                    </div>
                </div>

                {/* Toast feedback */}
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

                {/* BANNER 360° DE ESTADO & GOBERNANZA */}
                <Card className="shadow-sm border-l-4 border-indigo-600 p-4 sm:p-6 bg-gradient-to-r from-indigo-50/50 via-white to-white dark:from-indigo-950/20 dark:via-gray-800 dark:to-gray-800">
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div className="flex items-start gap-4">
                            <div className="w-16 h-16 rounded-2xl bg-indigo-600 text-white font-extrabold text-2xl flex items-center justify-center shadow-md flex-shrink-0">
                                {tenant.name.substring(0, 2).toUpperCase()}
                            </div>
                            <div className="space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                        {tenant.name}
                                    </h2>
                                    <Badge
                                        color={
                                            tenant.status === "active" ? "success" :
                                            tenant.status === "suspended" ? "failure" : "warning"
                                        }
                                        className="capitalize font-semibold"
                                    >
                                        {tenant.status === "active" ? "Activo" :
                                         tenant.status === "suspended" ? "Suspendido" : "Inactivo"}
                                    </Badge>
                                    <Badge
                                        color={
                                            tenant.request === "approved" ? "info" :
                                            tenant.request === "rejected" ? "failure" : "warning"
                                        }
                                        className="capitalize"
                                    >
                                        Solicitud: {tenant.request}
                                    </Badge>
                                </div>
                                <div className="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    <a
                                        href={`http://${primaryDomain}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 font-mono font-medium"
                                    >
                                        {primaryDomain} <HiExternalLink className="w-3.5 h-3.5" />
                                    </a>
                                    <span>Zona Horaria: {tenant.timezone || "UTC"}</span>
                                    <span>Moneda: {tenant.currency || "USD"}</span>
                                </div>
                            </div>
                        </div>

                        {/* Botones de Gobernanza */}
                        <div className="flex flex-wrap items-center gap-2">
                            {tenant.status !== "active" ? (
                                <Button
                                    size="xs"
                                    color="success"
                                    onClick={() => {
                                        setSelectedAction("activate");
                                        setGovernanceModalOpen(true);
                                    }}
                                >
                                    <HiCheckCircle className="w-4 h-4 mr-1" />
                                    Activar Tienda
                                </Button>
                            ) : (
                                <Button
                                    size="xs"
                                    color="failure"
                                    onClick={() => {
                                        setSelectedAction("suspend");
                                        setGovernanceModalOpen(true);
                                    }}
                                >
                                    <HiLockClosed className="w-4 h-4 mr-1" />
                                    Suspender Tienda
                                </Button>
                            )}

                            {tenant.request === "in progress" && (
                                <Button
                                    size="xs"
                                    color="info"
                                    onClick={() => {
                                        setSelectedAction("approve");
                                        setGovernanceModalOpen(true);
                                    }}
                                >
                                    <HiCheckCircle className="w-4 h-4 mr-1" />
                                    Aprobar Solicitud
                                </Button>
                            )}

                            <Button
                                size="xs"
                                color="light"
                                onClick={() => {
                                    setSelectedAction("notes");
                                    setGovernanceModalOpen(true);
                                }}
                            >
                                <HiPencilAlt className="w-4 h-4 mr-1" />
                                Notas Internas
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* METRICAS KPI 360° */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Ventas Acumuladas
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    ${(metrics?.total_sales_usd || 0).toFixed(2)}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    {metrics?.total_orders_count || 0} órdenes registradas
                                </p>
                            </div>
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-xl">
                                <HiCurrencyDollar className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-blue-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Comisiones Generadas
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    ${(metrics?.total_commissions_usd || 0).toFixed(2)}
                                </h3>
                                <p className="text-xs text-blue-600 font-medium mt-1">
                                    Ingresos para el Marketplace
                                </p>
                            </div>
                            <div className="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-xl">
                                <LuReceipt className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-amber-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Liquidaciones / Payouts
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    ${(metrics?.total_payouts_settled_usd || 0).toFixed(2)}
                                </h3>
                                <p className="text-xs text-amber-600 font-medium mt-1">
                                    ${(metrics?.total_payouts_pending_usd || 0).toFixed(2)} pendiente por liquidar
                                </p>
                            </div>
                            <div className="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 rounded-xl">
                                <TbBuildingBank className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-purple-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Productos & Soporte
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.products_published_count || 0} Prod.
                                </h3>
                                <p className="text-xs text-purple-600 font-medium mt-1">
                                    {metrics?.open_tickets_count || 0} tickets de soporte activos
                                </p>
                            </div>
                            <div className="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 rounded-xl">
                                <LuLifeBuoy className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* TABS CON INFORMACION DETALLADA 360° */}
                <Card className="shadow-sm">
                    <Tabs aria-label="Expediente 360" variant="underline">
                        {/* TAB 1: RESUMEN Y DUEÑOS */}
                        <TabItem active title="Propietarios & Contacto" icon={HiUserCircle}>
                            <div className="pt-4 space-y-6">
                                <h3 className="text-base font-bold text-gray-900 dark:text-white">
                                    Cuentas Propietarias / Administradores de la Tienda
                                </h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {(!tenant.owners || tenant.owners.length === 0) ? (
                                        <p className="text-sm text-gray-500 italic">No hay propietarios registrados.</p>
                                    ) : (
                                        tenant.owners.map((owner) => (
                                            <div
                                                key={owner.id}
                                                className="p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center gap-4 bg-gray-50 dark:bg-gray-800/50"
                                            >
                                                <div className="w-12 h-12 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-lg flex-shrink-0">
                                                    {owner.name.substring(0, 1).toUpperCase()}
                                                </div>
                                                <div className="text-sm space-y-0.5 overflow-hidden">
                                                    <h4 className="font-bold text-gray-900 dark:text-white truncate">
                                                        {owner.name}
                                                    </h4>
                                                    <p className="text-xs text-gray-500 font-mono truncate">{owner.email}</p>
                                                    <p className="text-xs text-gray-500">{owner.phone || "Sin teléfono"}</p>
                                                    <Badge color={owner.is_active ? "success" : "failure"} className="w-fit text-[10px]">
                                                        {owner.is_active ? "Activo" : "Bloqueado"}
                                                    </Badge>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>

                                <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <h4 className="text-sm font-bold text-gray-900 dark:text-white mb-2">
                                        Dominios Configurados
                                    </h4>
                                    <div className="space-y-2">
                                        {tenant.domains?.map((d) => (
                                            <div
                                                key={d.id}
                                                className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg flex items-center justify-between text-xs font-mono"
                                            >
                                                <span>{d.domain}</span>
                                                <Badge color="indigo">Principal</Badge>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </TabItem>

                        {/* TAB 2: ÓRDENES RECIENTES */}
                        <TabItem title="Historial de Órdenes" icon={HiShoppingBag}>
                            <div className="pt-4 overflow-x-auto">
                                <Table hoverable>
                                    <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                        <TableHeadCell>N° Orden</TableHeadCell>
                                        <TableHeadCell>Cliente</TableHeadCell>
                                        <TableHeadCell>Total USD</TableHeadCell>
                                        <TableHeadCell>Pago</TableHeadCell>
                                        <TableHeadCell>Estado</TableHeadCell>
                                        <TableHeadCell>Fecha</TableHeadCell>
                                    </TableHead>
                                    <TableBody className="divide-y text-xs">
                                        {orders.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center py-6 text-gray-400">
                                                    No se registran órdenes recientes para esta tienda.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            orders.map((ord: any) => (
                                                <TableRow key={ord.id}>
                                                    <TableCell className="font-mono font-semibold text-blue-600">
                                                        {ord.order_number || ord.id.substring(0, 8)}
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {ord.customer_name || ord.shipping_address?.full_name || "Cliente"}
                                                    </TableCell>
                                                    <TableCell className="font-bold">
                                                        ${parseFloat(ord.total_usd || "0").toFixed(2)}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            color={ord.payment_status === "paid" ? "success" : "warning"}
                                                            className="capitalize w-fit text-[10px]"
                                                        >
                                                            {ord.payment_status || "Pendiente"}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge color="info" className="capitalize w-fit text-[10px]">
                                                            {ord.status || "Pendiente"}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-gray-500">
                                                        {new Date(ord.created_at).toLocaleDateString("es-VE")}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </TabItem>

                        {/* TAB 3: FINANZAS & PAYOUTS */}
                        <TabItem title="Liquidaciones & Retiros" icon={TbBuildingBank}>
                            <div className="pt-4 overflow-x-auto">
                                <Table hoverable>
                                    <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                        <TableHeadCell>N° Solicitud</TableHeadCell>
                                        <TableHeadCell>Monto</TableHeadCell>
                                        <TableHeadCell>Método</TableHeadCell>
                                        <TableHeadCell>Referencia</TableHeadCell>
                                        <TableHeadCell>Estado</TableHeadCell>
                                        <TableHeadCell>Fecha</TableHeadCell>
                                    </TableHead>
                                    <TableBody className="divide-y text-xs">
                                        {payouts.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center py-6 text-gray-400">
                                                    No se registran solicitudes de retiro para este comercio.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            payouts.map((p: any) => (
                                                <TableRow key={p.id}>
                                                    <TableCell className="font-mono font-semibold text-blue-600">
                                                        {p.settlement_number}
                                                    </TableCell>
                                                    <TableCell className="font-bold">
                                                        ${parseFloat(p.net_amount || "0").toFixed(2)}
                                                    </TableCell>
                                                    <TableCell className="capitalize">
                                                        {p.payment_method?.replace("_", " ")}
                                                    </TableCell>
                                                    <TableCell className="font-mono">
                                                        {p.payment_reference || "-"}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            color={
                                                                p.status === "settled" || p.status === "paid" ? "success" :
                                                                p.status === "pending" ? "warning" : "failure"
                                                            }
                                                            className="capitalize w-fit text-[10px]"
                                                        >
                                                            {p.status}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-gray-500">
                                                        {new Date(p.created_at).toLocaleDateString("es-VE")}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </TabItem>

                        {/* TAB 4: TICKETS DE SOPORTE */}
                        <TabItem title="Tickets de Soporte" icon={LuLifeBuoy}>
                            <div className="pt-4 overflow-x-auto">
                                <Table hoverable>
                                    <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                        <TableHeadCell>Ticket</TableHeadCell>
                                        <TableHeadCell>Asunto</TableHeadCell>
                                        <TableHeadCell>Prioridad</TableHeadCell>
                                        <TableHeadCell>Estado</TableHeadCell>
                                        <TableHeadCell>Última Respuesta</TableHeadCell>
                                        <TableHeadCell className="text-right">Acción</TableHeadCell>
                                    </TableHead>
                                    <TableBody className="divide-y text-xs">
                                        {tickets.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center py-6 text-gray-400">
                                                    Esta tienda no tiene tickets de soporte abiertos.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            tickets.map((t: any) => (
                                                <TableRow key={t.id}>
                                                    <TableCell className="font-mono font-semibold text-gray-700 dark:text-gray-300">
                                                        {t.ticket_number}
                                                    </TableCell>
                                                    <TableCell className="font-medium max-w-[200px] truncate" title={t.subject}>
                                                        {t.subject}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            color={
                                                                t.priority === "urgent" ? "failure" :
                                                                t.priority === "high" ? "warning" : "gray"
                                                            }
                                                            className="capitalize w-fit text-[10px]"
                                                        >
                                                            {t.priority}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            color={
                                                                t.status === "open" ? "failure" :
                                                                t.status === "in_progress" ? "warning" : "success"
                                                            }
                                                            className="capitalize w-fit text-[10px]"
                                                        >
                                                            {t.status.replace("_", " ")}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-gray-500">
                                                        {t.last_reply_at ? new Date(t.last_reply_at).toLocaleDateString("es-VE") : "-"}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <Link
                                                            href={`/admin/backoffice/${user_id}/support`}
                                                            className="text-blue-600 hover:underline font-semibold"
                                                        >
                                                            Ver en Helpdesk
                                                        </Link>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </TabItem>

                        {/* TAB 5: NOTAS Y AUDITORIA */}
                        <TabItem title="Notas & Auditoría" icon={HiPencilAlt}>
                            <div className="pt-4 space-y-4">
                                <div>
                                    <h4 className="text-sm font-bold text-gray-900 dark:text-white mb-1">
                                        Notas Internas del Equipo Administrativo
                                    </h4>
                                    <div className="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-900 dark:text-amber-200">
                                        {tenant.data?.admin_notes || "No hay notas internas registradas para este comercio."}
                                    </div>
                                </div>

                                {tenant.data?.governance_history && tenant.data.governance_history.length > 0 && (
                                    <div>
                                        <h4 className="text-sm font-bold text-gray-900 dark:text-white mb-2">
                                            Historial de Acciones de Gobernanza
                                        </h4>
                                        <div className="space-y-2">
                                            {tenant.data.governance_history.map((h, i) => (
                                                <div
                                                    key={i}
                                                    className="p-3 rounded-lg border border-gray-200 dark:border-gray-700 text-xs space-y-1"
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <span className="font-semibold text-gray-900 dark:text-white">
                                                            Estado: {h.status} ({h.request})
                                                        </span>
                                                        <span className="text-gray-400">
                                                            {new Date(h.timestamp).toLocaleString("es-VE")}
                                                        </span>
                                                    </div>
                                                    <p className="text-gray-600 dark:text-gray-300">
                                                        <strong>Motivo:</strong> {h.reason}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </TabItem>
                    </Tabs>
                </Card>

                {/* MODAL DE GOBERNANZA */}
                <Modal show={governanceModalOpen} onClose={() => setGovernanceModalOpen(false)} size="md">
                    <ModalHeader>
                        {selectedAction === "approve" && "Aprobar Solicitud del Comercio"}
                        {selectedAction === "suspend" && "Suspender Tienda Inquilina"}
                        {selectedAction === "activate" && "Reactivar Tienda Inquilina"}
                        {selectedAction === "notes" && "Actualizar Notas Administrativas"}
                    </ModalHeader>
                    <form onSubmit={handleGovernanceSubmit}>
                        <ModalBody className="space-y-4">
                            {selectedAction === "suspend" && (
                                <div className="p-3 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg text-xs">
                                    ⚠️ La suspensión deshabilitará temporalmente el storefront y el acceso público de la tienda.
                                </div>
                            )}

                            {selectedAction !== "notes" && (
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Motivo de la acción <span className="text-red-500">*</span>
                                    </label>
                                    <TextInput
                                        required
                                        placeholder="Ej: Infracción de políticas, verificación aprobada, etc."
                                        value={actionReason}
                                        onChange={(e) => setActionReason(e.target.value)}
                                    />
                                </div>
                            )}

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Notas Privadas (Visible solo para administradores)
                                </label>
                                <textarea
                                    className="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    rows={3}
                                    placeholder="Comentarios internos sobre el comercio, acuerdos, etc."
                                    value={adminNotes}
                                    onChange={(e) => setAdminNotes(e.target.value)}
                                />
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button color="gray" onClick={() => setGovernanceModalOpen(false)} disabled={submittingGovernance}>
                                Cancelar
                            </Button>
                            <Button
                                color={selectedAction === "suspend" ? "failure" : "blue"}
                                type="submit"
                                disabled={submittingGovernance}
                            >
                                {submittingGovernance ? <Spinner size="sm" className="mr-2" /> : <HiCheckCircle className="w-4 h-4 mr-2" />}
                                Guardar Cambios
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>
            </div>
        </Dashboard>
    );
};

export default AdminTenantDetail360Page;
