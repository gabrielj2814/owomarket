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
    HiCheckCircle,
    HiCreditCard,
    HiCurrencyDollar,
    HiHome,
    HiInformationCircle,
    HiOutlineExclamation,
    HiRefresh,
    HiSearch,
    HiXCircle,
} from "react-icons/hi";
import { TbBuildingBank } from "react-icons/tb";

interface PayoutSettlement {
    id: string;
    settlement_number: string;
    tenant_id: string;
    type: string;
    gross_sales_amount: number;
    commission_amount: number;
    net_amount: number;
    currency: string;
    status: "pending" | "settled" | "cancelled" | "paid" | "rejected";
    payment_method: string;
    payment_reference?: string | null;
    settled_at?: string | null;
    created_at: string;
    notes?: string | null;
    tenant?: {
        id: string;
        name: string;
        slug: string;
    };
    metadata?: {
        user_id?: string;
        payment_details?: {
            bank_name?: string;
            id_number?: string;
            phone?: string;
            binance_id?: string;
            pay_id?: string;
            network?: string;
            beneficiary_name?: string;
        };
        rejection_reason?: string;
    };
}

interface AdminPayoutsIndexPageProps {
    title?: string;
    user_id: string;
    payouts: PayoutSettlement[];
    pagination: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
    metrics: {
        pending_count: number;
        pending_amount_usd: number;
        paid_count: number;
        paid_amount_usd: number;
        active_rate: number;
    };
    active_rate: number;
}

const AdminPayoutsIndexPage: FC<AdminPayoutsIndexPageProps> = ({
    title = "Centro de Liquidaciones & Payouts - OwOMarket",
    user_id,
    payouts: initialPayouts = [],
    pagination: initialPagination,
    metrics: initialMetrics,
    active_rate = 1,
}) => {
    const [payoutsList, setPayoutsList] = useState<PayoutSettlement[]>(initialPayouts);
    const [pagination, setPagination] = useState(initialPagination);
    const [metrics, setMetrics] = useState(initialMetrics);
    const [loading, setLoading] = useState(false);

    // Filtros
    const [statusFilter, setStatusFilter] = useState("all");
    const [paymentMethodFilter, setPaymentMethodFilter] = useState("all");
    const [searchTerm, setSearchTerm] = useState("");

    // Modal de Aprobación
    const [approvingPayout, setApprovingPayout] = useState<PayoutSettlement | null>(null);
    const [paymentReference, setPaymentReference] = useState("");
    const [approvalNotes, setApprovalNotes] = useState("");
    const [submittingApproval, setSubmittingApproval] = useState(false);

    // Modal de Rechazo
    const [rejectingPayout, setRejectingPayout] = useState<PayoutSettlement | null>(null);
    const [rejectionReason, setRejectionReason] = useState("");
    const [submittingRejection, setSubmittingRejection] = useState(false);

    // Feedback
    const [toastMessage, setToastMessage] = useState<{ type: "success" | "error"; text: string } | null>(null);

    const fetchPayouts = async (page = 1) => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (statusFilter !== "all") params.append("status", statusFilter);
            if (paymentMethodFilter !== "all") params.append("payment_method", paymentMethodFilter);
            if (searchTerm.trim()) params.append("search", searchTerm.trim());
            params.append("page", page.toString());

            const response = await axios.get(`/admin/api/payouts?${params.toString()}`);
            if (response.data?.status === "success") {
                setPayoutsList(response.data.data.payouts || []);
                setPagination(response.data.data.pagination);
                setMetrics(response.data.data.metrics);
            }
        } catch (error: any) {
            console.error("Error al cargar payouts:", error);
            setToastMessage({
                type: "error",
                text: "No se pudieron obtener las solicitudes de liquidación.",
            });
        } finally {
            setLoading(false);
        }
    };

    const handleApplyFilters = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        fetchPayouts(1);
    };

    const handleApproveSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!approvingPayout) return;
        if (!paymentReference.trim()) {
            setToastMessage({ type: "error", text: "Debe ingresar el número de referencia o TXID." });
            return;
        }

        setSubmittingApproval(true);
        try {
            const response = await axios.post(`/admin/api/payouts/${approvingPayout.id}/approve`, {
                payment_reference: paymentReference.trim(),
                notes: approvalNotes.trim() || undefined,
            });

            if (response.data?.status === "success") {
                setToastMessage({
                    type: "success",
                    text: `Solicitud ${approvingPayout.settlement_number} aprobada y marcada como liquidada.`,
                });
                setApprovingPayout(null);
                setPaymentReference("");
                setApprovalNotes("");
                fetchPayouts(pagination?.current_page || 1);
            }
        } catch (error: any) {
            setToastMessage({
                type: "error",
                text: error.response?.data?.message || "Error al aprobar la liquidación.",
            });
        } finally {
            setSubmittingApproval(false);
        }
    };

    const handleRejectSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!rejectingPayout) return;
        if (!rejectionReason.trim()) {
            setToastMessage({ type: "error", text: "Debe indicar el motivo del rechazo." });
            return;
        }

        setSubmittingRejection(true);
        try {
            const response = await axios.post(`/admin/api/payouts/${rejectingPayout.id}/reject`, {
                rejection_reason: rejectionReason.trim(),
            });

            if (response.data?.status === "success") {
                setToastMessage({
                    type: "success",
                    text: `Solicitud ${rejectingPayout.settlement_number} rechazada exitosamente.`,
                });
                setRejectingPayout(null);
                setRejectionReason("");
                fetchPayouts(pagination?.current_page || 1);
            }
        } catch (error: any) {
            setToastMessage({
                type: "error",
                text: error.response?.data?.message || "Error al procesar el rechazo.",
            });
        } finally {
            setSubmittingRejection(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />
            <div className="p-4 sm:p-6 space-y-6 max-w-7xl mx-auto">
                {/* Header y Breadcrumbs */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <Breadcrumb className="mb-2">
                            <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                                Panel Global
                            </BreadcrumbItem>
                            <BreadcrumbItem>Liquidaciones & Payouts</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiCurrencyDollar className="text-emerald-500 w-8 h-8" />
                            Aprobación de Retiros & Liquidaciones
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Audite, verifique referencias bancarias y apruebe o rechace solicitudes de retiro de los inquilinos.
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button
                            color="light"
                            size="sm"
                            onClick={() => fetchPayouts(pagination?.current_page || 1)}
                            disabled={loading}
                        >
                            <HiRefresh className={`w-4 h-4 mr-2 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                    </div>
                </div>

                {/* Toasts */}
                {toastMessage && (
                    <div
                        className={`p-4 rounded-lg flex items-center justify-between text-sm ${
                            toastMessage.type === "success"
                                ? "bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-300 border border-green-200 dark:border-green-800"
                                : "bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800"
                        }`}
                    >
                        <span>{toastMessage.text}</span>
                        <button
                            onClick={() => setToastMessage(null)}
                            className="font-bold text-lg leading-none hover:opacity-75 ml-4"
                        >
                            &times;
                        </button>
                    </div>
                )}

                {/* Métricas KPI Rápidas */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-l-4 border-amber-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Retiros Pendientes
                                </p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    {metrics?.pending_count || 0}
                                </h3>
                                <p className="text-xs text-amber-600 font-medium mt-1">
                                    ${(metrics?.pending_amount_usd || 0).toFixed(2)} USD por liquidar
                                </p>
                            </div>
                            <div className="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 rounded-full">
                                <HiOutlineExclamation className="w-6 h-6" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Total Liquidado (Pagado)
                                </p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    ${(metrics?.paid_amount_usd || 0).toFixed(2)}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    {metrics?.paid_count || 0} operaciones procesadas
                                </p>
                            </div>
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-full">
                                <HiCheckCircle className="w-6 h-6" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-blue-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Tasa Activa BCV
                                </p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    Bs. {(metrics?.active_rate || active_rate).toFixed(2)}
                                </h3>
                                <p className="text-xs text-blue-600 font-medium mt-1">
                                    Tasa oficial del sistema
                                </p>
                            </div>
                            <div className="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-full">
                                <TbBuildingBank className="w-6 h-6" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-purple-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Métodos Habilitados
                                </p>
                                <h3 className="text-lg font-bold text-gray-900 dark:text-white mt-1">
                                    Pago Móvil / Binance
                                </h3>
                                <p className="text-xs text-purple-600 font-medium mt-1">
                                    Con comprobante obligatorio
                                </p>
                            </div>
                            <div className="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 rounded-full">
                                <HiCreditCard className="w-6 h-6" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Filtros y Búsqueda */}
                <Card className="shadow-sm">
                    <form onSubmit={handleApplyFilters} className="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                        <div className="sm:col-span-4">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Buscar por N° Solicitud, Tienda o Ref
                            </label>
                            <TextInput
                                icon={HiSearch}
                                placeholder="Ej: PAY-202608..., 004829..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>

                        <div className="sm:col-span-3">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Estado
                            </label>
                            <select
                                className="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                            >
                                <option value="all">Todos los estados</option>
                                <option value="pending">Pendientes de Aprobación</option>
                                <option value="settled">Liquidados (Pagados)</option>
                                <option value="cancelled">Rechazados / Cancelados</option>
                            </select>
                        </div>

                        <div className="sm:col-span-3">
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Método de Pago
                            </label>
                            <select
                                className="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                value={paymentMethodFilter}
                                onChange={(e) => setPaymentMethodFilter(e.target.value)}
                            >
                                <option value="all">Todos los métodos</option>
                                <option value="pago_movil">Pago Móvil (Bs)</option>
                                <option value="binance_pay">Binance Pay (USDT)</option>
                            </select>
                        </div>

                        <div className="sm:col-span-2">
                            <Button type="submit" color="blue" className="w-full" disabled={loading}>
                                <HiSearch className="w-4 h-4 mr-2" />
                                Filtrar
                            </Button>
                        </div>
                    </form>
                </Card>

                {/* Tabla de Payouts */}
                <Card className="shadow-sm overflow-hidden p-0">
                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs uppercase text-gray-700 dark:text-gray-300">
                                <TableHeadCell>N° Solicitud</TableHeadCell>
                                <TableHeadCell>Tienda Inquilina</TableHeadCell>
                                <TableHeadCell>Monto</TableHeadCell>
                                <TableHeadCell>Método & Destino</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell>Fecha</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {payoutsList.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-8 text-gray-500 dark:text-gray-400">
                                            No se encontraron solicitudes de retiro con los filtros seleccionados.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    payoutsList.map((payout) => {
                                        const details = payout.metadata?.payment_details || {};
                                        const vesEquivalent = (payout.net_amount * (metrics?.active_rate || 1)).toFixed(2);

                                        return (
                                            <TableRow key={payout.id} className="bg-white dark:bg-gray-800">
                                                <TableCell className="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400">
                                                    {payout.settlement_number}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-semibold text-gray-900 dark:text-white">
                                                        {payout.tenant?.name || "Tienda Inquilina"}
                                                    </span>
                                                    <span className="block text-xs text-gray-500">
                                                        {payout.tenant?.slug ? `${payout.tenant.slug}.owomarket.local` : "-"}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-base font-bold text-gray-900 dark:text-white">
                                                        ${payout.net_amount.toFixed(2)}
                                                    </span>
                                                    <span className="block text-xs text-gray-500">
                                                        ≈ Bs. {vesEquivalent}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    <Badge
                                                        color={payout.payment_method === "pago_movil" ? "indigo" : "warning"}
                                                        className="w-fit mb-1 capitalize"
                                                    >
                                                        {payout.payment_method === "pago_movil" ? "Pago Móvil" : "Binance Pay"}
                                                    </Badge>
                                                    {payout.payment_method === "pago_movil" ? (
                                                        <div className="text-gray-600 dark:text-gray-300">
                                                            <p><strong>Banco:</strong> {details.bank_name || "-"}</p>
                                                            <p><strong>Cédula:</strong> {details.id_number || "-"}</p>
                                                            <p><strong>Telf:</strong> {details.phone || "-"}</p>
                                                        </div>
                                                    ) : (
                                                        <div className="text-gray-600 dark:text-gray-300">
                                                            <p><strong>Binance Pay ID:</strong> {details.pay_id || details.binance_id || "-"}</p>
                                                            <p><strong>Red:</strong> {details.network || "USDT (TRC20 / BEP20)"}</p>
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {payout.status === "pending" && (
                                                        <Badge color="warning" icon={HiOutlineExclamation}>
                                                            Pendiente
                                                        </Badge>
                                                    )}
                                                    {(payout.status === "settled" || payout.status === "paid") && (
                                                        <div>
                                                            <Badge color="success" icon={HiCheckCircle}>
                                                                Liquidado
                                                            </Badge>
                                                            {payout.payment_reference && (
                                                                <span className="block text-[11px] font-mono text-gray-500 mt-1">
                                                                    Ref: {payout.payment_reference}
                                                                </span>
                                                            )}
                                                        </div>
                                                    )}
                                                    {(payout.status === "cancelled" || payout.status === "rejected") && (
                                                        <div>
                                                            <Badge color="failure" icon={HiXCircle}>
                                                                Rechazado
                                                            </Badge>
                                                            {payout.notes && (
                                                                <span className="block text-[11px] text-red-500 mt-1 truncate max-w-[150px]" title={payout.notes}>
                                                                    {payout.notes}
                                                                </span>
                                                            )}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-xs text-gray-500 dark:text-gray-400">
                                                    {new Date(payout.created_at).toLocaleDateString("es-VE", {
                                                        year: "numeric",
                                                        month: "short",
                                                        day: "numeric",
                                                        hour: "2-digit",
                                                        minute: "2-digit",
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right space-x-2">
                                                    {payout.status === "pending" ? (
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Button
                                                                size="xs"
                                                                color="success"
                                                                onClick={() => {
                                                                    setApprovingPayout(payout);
                                                                    setPaymentReference("");
                                                                    setApprovalNotes("");
                                                                }}
                                                            >
                                                                <HiCheckCircle className="w-4 h-4 mr-1" />
                                                                Aprobar
                                                            </Button>
                                                            <Button
                                                                size="xs"
                                                                color="failure"
                                                                onClick={() => {
                                                                    setRejectingPayout(payout);
                                                                    setRejectionReason("");
                                                                }}
                                                            >
                                                                <HiXCircle className="w-4 h-4 mr-1" />
                                                                Rechazar
                                                            </Button>
                                                        </div>
                                                    ) : (
                                                        <span className="text-xs text-gray-400 italic">
                                                            {payout.status === "settled" || payout.status === "paid" ? "Completado" : "Rechazado"}
                                                        </span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Paginación */}
                    {pagination && pagination.last_page > 1 && (
                        <div className="p-4 flex items-center justify-between border-t border-gray-200 dark:border-gray-700">
                            <span className="text-xs text-gray-500">
                                Mostrando página {pagination.current_page} de {pagination.last_page} ({pagination.total} registros)
                            </span>
                            <div className="flex gap-2">
                                <Button
                                    size="xs"
                                    color="light"
                                    disabled={pagination.current_page <= 1 || loading}
                                    onClick={() => fetchPayouts(pagination.current_page - 1)}
                                >
                                    Anterior
                                </Button>
                                <Button
                                    size="xs"
                                    color="light"
                                    disabled={pagination.current_page >= pagination.last_page || loading}
                                    onClick={() => fetchPayouts(pagination.current_page + 1)}
                                >
                                    Siguiente
                                </Button>
                            </div>
                        </div>
                    )}
                </Card>

                {/* Modal Aprobación de Payout */}
                <Modal show={!!approvingPayout} onClose={() => setApprovingPayout(null)} size="md">
                    <ModalHeader>
                        Aprobar Liquidación {approvingPayout?.settlement_number}
                    </ModalHeader>
                    <form onSubmit={handleApproveSubmit}>
                        <ModalBody className="space-y-4">
                            <div className="bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-lg border border-emerald-200 dark:border-emerald-800 text-sm">
                                <p className="font-semibold text-emerald-800 dark:text-emerald-300">
                                    Tienda: {approvingPayout?.tenant?.name}
                                </p>
                                <p className="text-emerald-700 dark:text-emerald-400 text-lg font-bold mt-1">
                                    Monto: ${approvingPayout?.net_amount.toFixed(2)} USD
                                    <span className="text-xs font-normal ml-2">
                                        (≈ Bs. {((approvingPayout?.net_amount || 0) * (metrics?.active_rate || 1)).toFixed(2)})
                                    </span>
                                </p>
                                <div className="mt-2 text-xs text-emerald-900 dark:text-emerald-200">
                                    <strong>Destino: </strong>
                                    {approvingPayout?.payment_method === "pago_movil" ? (
                                        <span>
                                            Pago Móvil {approvingPayout?.metadata?.payment_details?.bank_name} -{" "}
                                            {approvingPayout?.metadata?.payment_details?.phone} (
                                            {approvingPayout?.metadata?.payment_details?.id_number})
                                        </span>
                                    ) : (
                                        <span>
                                            Binance Pay ID: {approvingPayout?.metadata?.payment_details?.pay_id}
                                        </span>
                                    )}
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    N° de Referencia Bancaria / Binance TXID <span className="text-red-500">*</span>
                                </label>
                                <TextInput
                                    required
                                    placeholder="Ej: 00482910482 o 987654321"
                                    value={paymentReference}
                                    onChange={(e) => setPaymentReference(e.target.value)}
                                />
                                <p className="text-[11px] text-gray-500 mt-1">
                                    Comprobante bancario que respalda la transferencia.
                                </p>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Notas u observaciones (Opcional)
                                </label>
                                <textarea
                                    className="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    rows={2}
                                    placeholder="Ej: Transferencia efectuada desde cuenta jurídica Banesco."
                                    value={approvalNotes}
                                    onChange={(e) => setApprovalNotes(e.target.value)}
                                />
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button color="gray" onClick={() => setApprovingPayout(null)} disabled={submittingApproval}>
                                Cancelar
                            </Button>
                            <Button color="success" type="submit" disabled={submittingApproval}>
                                {submittingApproval ? <Spinner size="sm" className="mr-2" /> : <HiCheckCircle className="w-4 h-4 mr-2" />}
                                Confirmar y Liquidar
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>

                {/* Modal Rechazo de Payout */}
                <Modal show={!!rejectingPayout} onClose={() => setRejectingPayout(null)} size="md">
                    <ModalHeader>
                        Rechazar Solicitud {rejectingPayout?.settlement_number}
                    </ModalHeader>
                    <form onSubmit={handleRejectSubmit}>
                        <ModalBody className="space-y-4">
                            <div className="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg border border-red-200 dark:border-red-800 text-sm">
                                <p className="font-semibold text-red-800 dark:text-red-300">
                                    Tienda: {rejectingPayout?.tenant?.name}
                                </p>
                                <p className="text-red-700 dark:text-red-400 font-bold mt-1">
                                    Monto: ${rejectingPayout?.net_amount.toFixed(2)} USD
                                </p>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Motivo del Rechazo <span className="text-red-500">*</span>
                                </label>
                                <textarea
                                    required
                                    className="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    rows={3}
                                    placeholder="Indique claramente la razón por la cual se rechaza el retiro (ej: datos bancarios inválidos, cuenta no coincide con el titular, etc.)"
                                    value={rejectionReason}
                                    onChange={(e) => setRejectionReason(e.target.value)}
                                />
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button color="gray" onClick={() => setRejectingPayout(null)} disabled={submittingRejection}>
                                Cancelar
                            </Button>
                            <Button color="failure" type="submit" disabled={submittingRejection}>
                                {submittingRejection ? <Spinner size="sm" className="mr-2" /> : <HiXCircle className="w-4 h-4 mr-2" />}
                                Rechazar Solicitud
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>
            </div>
        </Dashboard>
    );
};

export default AdminPayoutsIndexPage;
