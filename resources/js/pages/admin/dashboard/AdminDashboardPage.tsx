import Dashboard from "@/components/layouts/Dashboard";
import { Head, Link } from "@inertiajs/react";
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
} from "flowbite-react";
import { FC } from "react";
import {
    HiArrowRight,
    HiChartPie,
    HiCheckCircle,
    HiClock,
    HiCurrencyDollar,
    HiHome,
    HiOutlineExclamation,
    HiOutlineShoppingCart,
    HiRefresh,
    HiShoppingBag,
    HiUsers,
} from "react-icons/hi";
import { LuBuilding2, LuLifeBuoy, LuReceipt, LuStore, LuUserCheck, LuWallet } from "react-icons/lu";
import { TbBuildingBank } from "react-icons/tb";

interface AdminDashboardPageProps {
    title?: string;
    user_id: string;
    user_name?: string;
    user_email?: string;
    user_type?: string;
    user_avatar?: string;
    metrics?: {
        total_gmv_usd: number;
        total_gmv_ves: number;
        total_commission_usd: number;
        total_commission_ves: number;
        total_orders_count: number;
        paid_orders_count: number;
        total_tenants: number;
        active_tenants: number;
        pending_tenants: number;
        suspended_tenants: number;
        total_customers: number;
        pending_payouts_count: number;
        pending_payouts_amount_usd: number;
        open_tickets_count: number;
        waiting_tickets_count: number;
        active_exchange_rate: number;
    };
    recent_activity?: {
        orders: any[];
        tickets: any[];
        payouts: any[];
    };
}

const AdminDashboardPage: FC<AdminDashboardPageProps> = ({
    title = "Dashboard Ejecutivo Super Admin - OwOMarket",
    user_id,
    user_name = "Super Admin",
    user_email = "",
    user_type = "super_admin",
    user_avatar = "",
    metrics,
    recent_activity,
}) => {
    return (
        <>
            <Head title={title} />
            <Dashboard user_uuid={user_id}>
                {/* Header Superior y Bienvenida */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <Breadcrumb className="mb-2">
                            <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                                Inicio
                            </BreadcrumbItem>
                            <BreadcrumbItem>Panel de Control Ejecutivo</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiChartPie className="text-blue-600 w-8 h-8" />
                            Hola, {user_name} 👋
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Monitoreo centralizado en tiempo real de tiendas inquilinas, órdenes, comisiones y soporte.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-2 rounded-xl shadow-sm flex items-center gap-2">
                            <TbBuildingBank className="text-emerald-500 w-5 h-5" />
                            <div className="text-xs">
                                <span className="text-gray-400 block font-medium">Tasa BCV Activa</span>
                                <span className="text-gray-900 dark:text-white font-bold">
                                    Bs. {(metrics?.active_exchange_rate || 1).toFixed(2)} / USD
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* ACCIONES RÁPIDAS DE ATENCIÓN URGENTE */}
                {((metrics?.pending_payouts_count || 0) > 0 || (metrics?.open_tickets_count || 0) > 0) && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        {(metrics?.pending_payouts_count || 0) > 0 && (
                            <div className="bg-gradient-to-r from-amber-500/10 to-orange-500/10 border border-amber-300 dark:border-amber-700 rounded-xl p-4 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="p-3 bg-amber-500 text-white rounded-lg shadow-sm">
                                        <LuWallet className="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-bold text-gray-900 dark:text-white">
                                            {metrics?.pending_payouts_count} Solicitud(es) de Retiro Pendiente(s)
                                        </h4>
                                        <p className="text-xs text-gray-600 dark:text-gray-300">
                                            Monto acumulado por liquidar: ${metrics?.pending_payouts_amount_usd?.toFixed(2)} USD
                                        </p>
                                    </div>
                                </div>
                                <Link
                                    href={`/admin/backoffice/${user_id}/payouts`}
                                    className="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold rounded-lg shadow transition flex items-center gap-1"
                                >
                                    Revisar <HiArrowRight />
                                </Link>
                            </div>
                        )}

                        {(metrics?.open_tickets_count || 0) > 0 && (
                            <div className="bg-gradient-to-r from-blue-500/10 to-indigo-500/10 border border-blue-300 dark:border-blue-700 rounded-xl p-4 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="p-3 bg-blue-600 text-white rounded-lg shadow-sm">
                                        <LuLifeBuoy className="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-bold text-gray-900 dark:text-white">
                                            {metrics?.open_tickets_count} Ticket(s) de Soporte Activo(s)
                                        </h4>
                                        <p className="text-xs text-gray-600 dark:text-gray-300">
                                            Consultas técnicas y reclamos de inquilinos y compradores.
                                        </p>
                                    </div>
                                </div>
                                <Link
                                    href={`/admin/backoffice/${user_id}/support`}
                                    className="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow transition flex items-center gap-1"
                                >
                                    Atender <HiArrowRight />
                                </Link>
                            </div>
                        )}
                    </div>
                )}

                {/* KPI METRICS GRID */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    {/* GMV TOTAL */}
                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    GMV Global (Ventas)
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    ${(metrics?.total_gmv_usd || 0).toFixed(2)}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    ≈ Bs. {(metrics?.total_gmv_ves || 0).toFixed(2)}
                                </p>
                            </div>
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-xl">
                                <HiCurrencyDollar className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    {/* COMISIONES MARKETPLACE */}
                    <Card className="border-l-4 border-blue-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Comisiones OwOMarket
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    ${(metrics?.total_commission_usd || 0).toFixed(2)}
                                </h3>
                                <p className="text-xs text-blue-600 font-medium mt-1">
                                    Ingresos netos por plataforma
                                </p>
                            </div>
                            <div className="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-xl">
                                <LuReceipt className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    {/* TIENDAS INQUILINAS */}
                    <Card className="border-l-4 border-indigo-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Tiendas Registradas
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_tenants || 0}
                                </h3>
                                <p className="text-xs text-indigo-600 font-medium mt-1">
                                    {metrics?.active_tenants || 0} activas • {metrics?.pending_tenants || 0} por aprobar
                                </p>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-xl">
                                <LuStore className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    {/* CLIENTES CENTRALES */}
                    <Card className="border-l-4 border-purple-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Clientes Compradores
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_customers || 0}
                                </h3>
                                <p className="text-xs text-purple-600 font-medium mt-1">
                                    Cuentas globales activas
                                </p>
                            </div>
                            <div className="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 rounded-xl">
                                <HiUsers className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* ACCESOS DIRECTOS A MÓDULOS DE CONTROL */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <Link
                        href={`/admin/backoffice/${user_id}/payouts`}
                        className="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-emerald-500 transition flex items-center gap-4 group"
                    >
                        <div className="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-xl group-hover:scale-110 transition">
                            <LuWallet className="w-6 h-6" />
                        </div>
                        <div>
                            <h4 className="text-sm font-bold text-gray-900 dark:text-white">Aprobar Payouts</h4>
                            <p className="text-xs text-gray-500">Liquidaciones bancarias</p>
                        </div>
                    </Link>

                    <Link
                        href={`/admin/backoffice/${user_id}/support`}
                        className="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-blue-500 transition flex items-center gap-4 group"
                    >
                        <div className="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-xl group-hover:scale-110 transition">
                            <LuLifeBuoy className="w-6 h-6" />
                        </div>
                        <div>
                            <h4 className="text-sm font-bold text-gray-900 dark:text-white">Mesa de Soporte</h4>
                            <p className="text-xs text-gray-500">Helpdesk y Tickets</p>
                        </div>
                    </Link>

                    <Link
                        href={`/admin/backoffice/${user_id}/tenants`}
                        className="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-indigo-500 transition flex items-center gap-4 group"
                    >
                        <div className="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-xl group-hover:scale-110 transition">
                            <LuStore className="w-6 h-6" />
                        </div>
                        <div>
                            <h4 className="text-sm font-bold text-gray-900 dark:text-white">Tiendas Inquilinas</h4>
                            <p className="text-xs text-gray-500">Aprobación y Backoffice SSO</p>
                        </div>
                    </Link>

                    <Link
                        href={`/admin/backoffice/${user_id}/exchange_rates`}
                        className="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-amber-500 transition flex items-center gap-4 group"
                    >
                        <div className="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 rounded-xl group-hover:scale-110 transition">
                            <TbBuildingBank className="w-6 h-6" />
                        </div>
                        <div>
                            <h4 className="text-sm font-bold text-gray-900 dark:text-white">Tasa de Cambio</h4>
                            <p className="text-xs text-gray-500">Gestión BCV y Paridad</p>
                        </div>
                    </Link>
                </div>

                {/* TABLAS DE ACTIVIDAD RECIENTE */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* SOLICITUDES DE RETIRO RECIENTES */}
                    <Card className="shadow-sm p-0 overflow-hidden">
                        <div className="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <TbBuildingBank className="text-amber-500" /> Solicitudes de Retiro Recientes
                            </h3>
                            <Link href={`/admin/backoffice/${user_id}/payouts`} className="text-xs text-blue-600 hover:underline font-semibold">
                                Ver todas
                            </Link>
                        </div>
                        <div className="overflow-x-auto">
                            <Table hoverable>
                                <TableHead className="bg-gray-50 dark:bg-gray-800 text-[11px]">
                                    <TableHeadCell>N° Solicitud</TableHeadCell>
                                    <TableHeadCell>Tienda</TableHeadCell>
                                    <TableHeadCell>Monto</TableHeadCell>
                                    <TableHeadCell>Estado</TableHeadCell>
                                </TableHead>
                                <TableBody className="divide-y text-xs">
                                    {!recent_activity?.payouts || recent_activity.payouts.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={4} className="text-center py-6 text-gray-400">
                                                No hay solicitudes de retiro recientes.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        recent_activity.payouts.map((payout: any) => (
                                            <TableRow key={payout.id}>
                                                <TableCell className="font-mono font-semibold text-blue-600">
                                                    {payout.settlement_number}
                                                </TableCell>
                                                <TableCell className="font-medium">{payout.tenant?.name || "Tienda"}</TableCell>
                                                <TableCell className="font-bold">${payout.net_amount?.toFixed(2)}</TableCell>
                                                <TableCell>
                                                    <Badge
                                                        color={
                                                            payout.status === "settled" || payout.status === "paid" ? "success" :
                                                            payout.status === "pending" ? "warning" : "failure"
                                                        }
                                                        className="text-[10px] capitalize w-fit"
                                                    >
                                                        {payout.status}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </Card>

                    {/* TICKETS DE SOPORTE RECIENTES */}
                    <Card className="shadow-sm p-0 overflow-hidden">
                        <div className="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <LuLifeBuoy className="text-blue-500" /> Mesa de Soporte Reciente
                            </h3>
                            <Link href={`/admin/backoffice/${user_id}/support`} className="text-xs text-blue-600 hover:underline font-semibold">
                                Ver todos
                            </Link>
                        </div>
                        <div className="overflow-x-auto">
                            <Table hoverable>
                                <TableHead className="bg-gray-50 dark:bg-gray-800 text-[11px]">
                                    <TableHeadCell>Ticket</TableHeadCell>
                                    <TableHeadCell>Origen</TableHeadCell>
                                    <TableHeadCell>Asunto</TableHeadCell>
                                    <TableHeadCell>Estado</TableHeadCell>
                                </TableHead>
                                <TableBody className="divide-y text-xs">
                                    {!recent_activity?.tickets || recent_activity.tickets.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={4} className="text-center py-6 text-gray-400">
                                                No hay tickets de soporte activos.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        recent_activity.tickets.map((ticket: any) => (
                                            <TableRow key={ticket.id}>
                                                <TableCell className="font-mono font-semibold text-gray-700 dark:text-gray-300">
                                                    {ticket.ticket_number}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        color={ticket.requester_type === "tenant_owner" || ticket.requester_type === "tenant" ? "purple" : "info"}
                                                        className="text-[10px] capitalize w-fit"
                                                    >
                                                        {ticket.requester_type === "tenant_owner" || ticket.requester_type === "tenant" ? "Tienda" : "Cliente"}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="font-medium truncate max-w-[140px]" title={ticket.subject}>
                                                    {ticket.subject}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        color={
                                                            ticket.status === "open" ? "failure" :
                                                            ticket.status === "in_progress" ? "warning" : "success"
                                                        }
                                                        className="text-[10px] capitalize w-fit"
                                                    >
                                                        {ticket.status.replace("_", " ")}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </Card>
                </div>
            </Dashboard>
        </>
    );
};

export default AdminDashboardPage;
