import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import AdminOrderServices from "@/Services/AdminOrderServices";
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
    HiCheckCircle,
    HiClock,
    HiCurrencyDollar,
    HiEye,
    HiHome,
    HiOutlineExclamation,
    HiOutlineRefresh,
    HiRefresh,
    HiSearch,
    HiShoppingBag,
    HiXCircle,
} from "react-icons/hi";
import { LuBuilding2, LuCreditCard, LuMapPin, LuPackageCheck, LuShieldAlert, LuStore, LuTruck } from "react-icons/lu";
import { TbBuildingBank } from "react-icons/tb";

interface TenantOption {
    id: string;
    name: string;
}

interface OrderItem {
    id: string;
    product_name: string;
    variant_name?: string | null;
    quantity: number;
    unit_price_usd: string;
    total_usd: string;
    sku?: string | null;
}

interface GlobalOrder {
    id: string;
    order_number: string;
    tenant_id: string;
    customer_id?: string | null;
    customer?: {
        name: string;
        email: string;
        phone?: string;
    };
    total_usd: string;
    total_ves?: string | null;
    exchange_rate_bcv?: string | null;
    payment_method?: string | null;
    payment_status: "pending" | "paid" | "refunded" | "cancelled" | "failed";
    payment_reference?: string | null;
    status: "pending" | "confirmed" | "processing" | "shipped" | "delivered" | "cancelled" | "refunded";
    shipping_tracking_number?: string | null;
    shipping_address?: {
        full_name?: string;
        phone?: string;
        state?: string;
        city?: string;
        address?: string;
    };
    created_at: string;
    items?: OrderItem[];
    metadata?: any;
}

interface Metrics {
    total_orders: number;
    total_gmv_usd: number;
    total_gmv_ves: number;
    paid_orders_count: number;
    pending_orders_count: number;
    cancelled_orders_count: number;
}

interface PaginationData {
    data: GlobalOrder[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface AdminGlobalOrdersPageProps {
    title?: string;
    user_id: string;
    orders_data: PaginationData;
    metrics: Metrics;
    tenants_list: TenantOption[];
    filters: {
        tenant_id: string;
        status: string;
        payment_status: string;
        search: string;
        date_from: string;
        date_to: string;
    };
}

const AdminGlobalOrdersPage: FC<AdminGlobalOrdersPageProps> = ({
    title = "Monitor Global de Órdenes & Disputas - OwOMarket",
    user_id,
    orders_data: initialPagination,
    metrics: initialMetrics,
    tenants_list = [],
    filters: initialFilters,
}) => {
    const [orders, setOrders] = useState<GlobalOrder[]>(initialPagination.data || []);
    const [pagination, setPagination] = useState({
        current_page: initialPagination.current_page || 1,
        last_page: initialPagination.last_page || 1,
        total: initialPagination.total || 0,
        per_page: initialPagination.per_page || 15,
    });
    const [metrics, setMetrics] = useState<Metrics>(initialMetrics);

    // Filtros
    const [search, setSearch] = useState(initialFilters.search || "");
    const [selectedTenant, setSelectedTenant] = useState(initialFilters.tenant_id || "");
    const [selectedStatus, setSelectedStatus] = useState(initialFilters.status || "");
    const [selectedPaymentStatus, setSelectedPaymentStatus] = useState(initialFilters.payment_status || "");
    const [dateFrom, setDateFrom] = useState(initialFilters.date_from || "");
    const [dateTo, setDateTo] = useState(initialFilters.date_to || "");

    const [loading, setLoading] = useState(false);
    const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null);

    // Modal de Detalle Completo
    const [detailModalOpen, setDetailModalOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<GlobalOrder | null>(null);
    const [loadingDetail, setLoadingDetail] = useState(false);

    // Modal de Disputa / Reembolso
    const [disputeModalOpen, setDisputeModalOpen] = useState(false);
    const [disputeType, setDisputeType] = useState<"refund" | "cancel">("refund");
    const [disputeReason, setDisputeReason] = useState("");
    const [disputeNotes, setDisputeNotes] = useState("");
    const [submittingDispute, setSubmittingDispute] = useState(false);
    const [confirmingPayment, setConfirmingPayment] = useState(false);

    const fetchOrders = async (page = 1) => {
        setLoading(true);
        try {
            const params: any = { page };
            if (search.trim()) params.search = search.trim();
            if (selectedTenant) params.tenant_id = selectedTenant;
            if (selectedStatus) params.status = selectedStatus;
            if (selectedPaymentStatus) params.payment_status = selectedPaymentStatus;
            if (dateFrom) params.date_from = dateFrom;
            if (dateTo) params.date_to = dateTo;

            const response = await AdminOrderServices.listar<any>(params);
            if (response?.status === "success") {
                const resData = response.data;
                setOrders(resData.orders.data);
                setPagination({
                    current_page: resData.orders.current_page,
                    last_page: resData.orders.last_page,
                    total: resData.orders.total,
                    per_page: resData.orders.per_page,
                });
                setMetrics(resData.metrics);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al consultar órdenes globales." });
        } finally {
            setLoading(false);
        }
    };

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchOrders(1);
    };

    const handleOpenDetail = async (order: GlobalOrder) => {
        setSelectedOrder(order);
        setDetailModalOpen(true);
        setLoadingDetail(true);
        try {
            const response = await AdminOrderServices.detalle<any>(order.id);
            if (response?.status === "success") {
                setSelectedOrder(response.data.order);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al cargar detalle de la orden." });
        } finally {
            setLoadingDetail(false);
        }
    };

    // Hallazgo A: confirmar el cobro de un pedido central no existia. Sin esto la comision
    // se quedaba en `awaiting_payment` para siempre, y eso bloquea tanto el cobro de la
    // plataforma como el `payout` a la tienda: el comerciante no cobraba su venta central.
    const handleConfirmPayment = async () => {
        if (!selectedOrder) return;

        setConfirmingPayment(true);
        try {
            const response = await AdminOrderServices.confirmarCobro(selectedOrder.id, {
                reference: selectedOrder.payment_reference || undefined,
            });

            if (response?.status === "success") {
                setToast({
                    type: "success",
                    text: response.message || "Cobro confirmado.",
                });
                setDetailModalOpen(false);
                fetchOrders(pagination.current_page);
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al confirmar el cobro.",
            });
        } finally {
            setConfirmingPayment(false);
        }
    };

    const handleDisputeSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedOrder) return;

        setSubmittingDispute(true);
        try {
            const response = await AdminOrderServices.resolverDisputa(selectedOrder.id, {
                resolution_type: disputeType as 'refund' | 'cancel',
                reason: disputeReason.trim(),
                notes: disputeNotes.trim() || undefined,
            });

            if (response?.status === "success") {
                setToast({
                    type: "success",
                    text: response.message || "Disputa resuelta exitosamente.",
                });
                setDisputeModalOpen(false);
                setDetailModalOpen(false);
                setDisputeReason("");
                setDisputeNotes("");
                fetchOrders(pagination.current_page);
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al procesar resolución de disputa.",
            });
        } finally {
            setSubmittingDispute(false);
        }
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
                            <BreadcrumbItem>Monitor Global de Órdenes</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiShoppingBag className="text-emerald-600 w-8 h-8" />
                            Monitor Global de Órdenes & Disputas
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Auditoría de transacciones multi-tienda, pasarelas de pago y mediación de disputas comerciales.
                        </p>
                    </div>

                    <Button
                        color="light"
                        size="sm"
                        onClick={() => fetchOrders(pagination.current_page)}
                        disabled={loading}
                    >
                        <HiRefresh className={`w-4 h-4 mr-1.5 ${loading ? "animate-spin" : ""}`} />
                        Actualizar
                    </Button>
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
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            GMV Total USD
                        </p>
                        <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                            ${(metrics?.total_gmv_usd || 0).toFixed(2)}
                        </h3>
                        <p className="text-[11px] text-emerald-600 font-medium mt-1">
                            Volumen bruto procesado
                        </p>
                    </Card>

                    <Card className="border-l-4 border-blue-500 shadow-sm">
                        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Total Órdenes
                        </p>
                        <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                            {metrics?.total_orders || 0}
                        </h3>
                        <p className="text-[11px] text-blue-600 font-medium mt-1">
                            En todo el marketplace
                        </p>
                    </Card>

                    <Card className="border-l-4 border-teal-500 shadow-sm">
                        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Órdenes Pagadas
                        </p>
                        <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                            {metrics?.paid_orders_count || 0}
                        </h3>
                        <p className="text-[11px] text-teal-600 font-medium mt-1">
                            Confirmadas con éxito
                        </p>
                    </Card>

                    <Card className="border-l-4 border-amber-500 shadow-sm">
                        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Pendientes de Pago
                        </p>
                        <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                            {metrics?.pending_orders_count || 0}
                        </h3>
                        <p className="text-[11px] text-amber-600 font-medium mt-1">
                            En proceso de verificación
                        </p>
                    </Card>

                    <Card className="border-l-4 border-rose-500 shadow-sm">
                        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Canceladas / Disputas
                        </p>
                        <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                            {metrics?.cancelled_orders_count || 0}
                        </h3>
                        <p className="text-[11px] text-rose-600 font-medium mt-1">
                            Reembolsos o cancelaciones
                        </p>
                    </Card>
                </div>

                {/* FILTROS AVANZADOS Y TABLA */}
                <Card className="shadow-sm">
                    <form onSubmit={handleFilterSubmit} className="space-y-3 mb-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                            <div className="lg:col-span-2">
                                <TextInput
                                    icon={HiSearch}
                                    placeholder="N° orden, referencia, tracking o cliente..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>

                            <Select
                                value={selectedTenant}
                                onChange={(e) => setSelectedTenant(e.target.value)}
                            >
                                <option value="">Todas las Tiendas</option>
                                {tenants_list.map((t) => (
                                    <option key={t.id} value={t.id}>
                                        {t.name}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                value={selectedStatus}
                                onChange={(e) => setSelectedStatus(e.target.value)}
                            >
                                <option value="">Estado de Orden (Todos)</option>
                                <option value="pending">Pendiente</option>
                                <option value="processing">En Preparación</option>
                                <option value="shipped">Despachado</option>
                                <option value="delivered">Entregado</option>
                                <option value="cancelled">Cancelado</option>
                                <option value="refunded">Reembolsado</option>
                            </Select>

                            <Select
                                value={selectedPaymentStatus}
                                onChange={(e) => setSelectedPaymentStatus(e.target.value)}
                            >
                                <option value="">Estado de Pago (Todos)</option>
                                <option value="paid">Pagado</option>
                                <option value="pending">Pendiente</option>
                                <option value="refunded">Reembolsado</option>
                                <option value="failed">Fallido</option>
                            </Select>
                        </div>

                        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                            <div className="flex items-center gap-2 text-xs text-gray-500">
                                <span>Desde:</span>
                                <input
                                    type="date"
                                    className="p-1.5 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                />
                                <span>Hasta:</span>
                                <input
                                    type="date"
                                    className="p-1.5 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                />
                            </div>

                            <Button type="submit" color="blue" size="sm" disabled={loading}>
                                <HiSearch className="w-4 h-4 mr-1.5" />
                                Aplicar Filtros
                            </Button>
                        </div>
                    </form>

                    {/* Tabla de Órdenes */}
                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                <TableHeadCell>N° Orden</TableHeadCell>
                                <TableHeadCell>Cliente</TableHeadCell>
                                <TableHeadCell>Total USD</TableHeadCell>
                                <TableHeadCell>Método / Referencia</TableHeadCell>
                                <TableHeadCell>Pago</TableHeadCell>
                                <TableHeadCell>Estado Despacho</TableHeadCell>
                                <TableHeadCell>Fecha</TableHeadCell>
                                <TableHeadCell className="text-right">Acción</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y text-xs">
                                {orders.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-8 text-gray-400">
                                            No se encontraron órdenes registradas con los filtros actuales.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    orders.map((ord) => (
                                        <TableRow key={ord.id}>
                                            <TableCell className="font-mono font-bold text-blue-600">
                                                {ord.order_number || ord.id.substring(0, 8)}
                                            </TableCell>
                                            <TableCell>
                                                <p className="font-semibold text-gray-900 dark:text-white">
                                                    {ord.customer?.name || ord.shipping_address?.full_name || "Cliente"}
                                                </p>
                                                <p className="text-[11px] text-gray-400 font-mono">
                                                    {ord.customer?.email || "Sin email"}
                                                </p>
                                            </TableCell>
                                            <TableCell className="font-extrabold text-gray-900 dark:text-white">
                                                ${parseFloat(ord.total_usd || "0").toFixed(2)}
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-0.5">
                                                    <Badge color="gray" className="capitalize w-fit text-[10px]">
                                                        {ord.payment_method?.replace("_", " ") || "No especificado"}
                                                    </Badge>
                                                    {ord.payment_reference && (
                                                        <p className="text-[10px] font-mono text-gray-500">
                                                            Ref: {ord.payment_reference}
                                                        </p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    color={
                                                        ord.payment_status === "paid" ? "success" :
                                                        ord.payment_status === "pending" ? "warning" :
                                                        ord.payment_status === "refunded" ? "purple" : "failure"
                                                    }
                                                    className="capitalize w-fit text-[10px]"
                                                >
                                                    {ord.payment_status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    color={
                                                        ord.status === "delivered" ? "success" :
                                                        ord.status === "shipped" ? "info" :
                                                        ord.status === "processing" ? "blue" :
                                                        ord.status === "pending" ? "warning" : "failure"
                                                    }
                                                    className="capitalize w-fit text-[10px]"
                                                >
                                                    {ord.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-gray-500">
                                                {new Date(ord.created_at).toLocaleDateString("es-VE")}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    size="xs"
                                                    color="light"
                                                    onClick={() => handleOpenDetail(ord)}
                                                    title="Ver Detalle y Disputa"
                                                >
                                                    <HiEye className="w-4 h-4 text-blue-600 mr-1" />
                                                    Auditar
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Paginación */}
                    {pagination.last_page > 1 && (
                        <div className="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Pagination
                                currentPage={pagination.current_page}
                                totalPages={pagination.last_page}
                                onPageChange={(page) => fetchOrders(page)}
                                showIcons
                                previousLabel="Anterior"
                                nextLabel="Siguiente"
                            />
                        </div>
                    )}
                </Card>

                {/* MODAL DE DETALLE COMPLETO DE ORDEN */}
                <Modal show={detailModalOpen} onClose={() => setDetailModalOpen(false)} size="4xl">
                    <ModalHeader>
                        Auditoría de Orden: {selectedOrder?.order_number || selectedOrder?.id}
                    </ModalHeader>
                    <ModalBody className="space-y-6">
                        {loadingDetail || !selectedOrder ? (
                            <div className="text-center py-12">
                                <Spinner size="xl" />
                                <p className="text-xs text-gray-500 mt-2">Cargando desglose de la transacción...</p>
                            </div>
                        ) : (
                            <>
                                {/* Header Resumen */}
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl text-xs">
                                    <div>
                                        <p className="text-gray-500">Comprador</p>
                                        <p className="font-bold text-gray-900 dark:text-white text-sm">
                                            {selectedOrder.customer?.name || selectedOrder.shipping_address?.full_name || "Cliente Anónimo"}
                                        </p>
                                        <p className="text-gray-400 font-mono">{selectedOrder.customer?.email}</p>
                                        <p className="text-gray-400">{selectedOrder.customer?.phone || selectedOrder.shipping_address?.phone}</p>
                                    </div>

                                    <div>
                                        <p className="text-gray-500">Pasarela & Pago</p>
                                        <p className="font-bold capitalize text-gray-900 dark:text-white text-sm">
                                            {selectedOrder.payment_method?.replace("_", " ")}
                                        </p>
                                        <p className="text-gray-400 font-mono">Ref: {selectedOrder.payment_reference || "N/A"}</p>
                                        {selectedOrder.payment_status === "pending" && (
                                            <p className="text-[11px] text-amber-600 dark:text-amber-500">
                                                Coteja esta referencia contra el banco antes de confirmar el cobro.
                                            </p>
                                        )}
                                        <Badge
                                            color={selectedOrder.payment_status === "paid" ? "success" : "warning"}
                                            className="w-fit mt-1"
                                        >
                                            {selectedOrder.payment_status}
                                        </Badge>
                                    </div>

                                    <div>
                                        <p className="text-gray-500">Total Liquidado</p>
                                        <p className="text-xl font-extrabold text-emerald-600">
                                            ${parseFloat(selectedOrder.total_usd || "0").toFixed(2)}
                                        </p>
                                        {selectedOrder.total_ves && (
                                            <p className="text-gray-500 font-mono">
                                                Bs. {parseFloat(selectedOrder.total_ves).toFixed(2)}
                                            </p>
                                        )}
                                        <p className="text-gray-400 text-[11px]">
                                            Fecha: {new Date(selectedOrder.created_at).toLocaleString("es-VE")}
                                        </p>
                                    </div>
                                </div>

                                {/* Dirección de Entrega */}
                                {selectedOrder.shipping_address && (
                                    <div className="p-3 border border-gray-200 dark:border-gray-700 rounded-lg text-xs space-y-1">
                                        <div className="flex items-center gap-1.5 font-bold text-gray-900 dark:text-white">
                                            <LuMapPin className="text-red-500" />
                                            Dirección de Despacho
                                        </div>
                                        <p className="text-gray-600 dark:text-gray-300">
                                            {selectedOrder.shipping_address.address || "-"}, {selectedOrder.shipping_address.city}, {selectedOrder.shipping_address.state}
                                        </p>
                                        {selectedOrder.shipping_tracking_number && (
                                            <p className="text-blue-600 font-mono">
                                                Guía / Tracking: {selectedOrder.shipping_tracking_number}
                                            </p>
                                        )}
                                    </div>
                                )}

                                {/* Items de la Orden */}
                                <div>
                                    <h4 className="text-xs font-bold text-gray-900 dark:text-white uppercase mb-2">
                                        Productos en la Orden
                                    </h4>
                                    <div className="overflow-x-auto">
                                        <Table hoverable>
                                            <TableHead className="text-xs bg-gray-100 dark:bg-gray-700">
                                                <TableHeadCell>Producto</TableHeadCell>
                                                <TableHeadCell>Cant.</TableHeadCell>
                                                <TableHeadCell>Precio Unit. USD</TableHeadCell>
                                                <TableHeadCell className="text-right">Subtotal</TableHeadCell>
                                            </TableHead>
                                            <TableBody className="divide-y text-xs">
                                                {(!selectedOrder.items || selectedOrder.items.length === 0) ? (
                                                    <TableRow>
                                                        <TableCell colSpan={4} className="text-center py-4 text-gray-400">
                                                            No hay desglose de productos para esta orden.
                                                        </TableCell>
                                                    </TableRow>
                                                ) : (
                                                    selectedOrder.items.map((item) => (
                                                        <TableRow key={item.id}>
                                                            <TableCell>
                                                                <p className="font-bold text-gray-900 dark:text-white">
                                                                    {item.product_name}
                                                                </p>
                                                                {item.variant_name && (
                                                                    <p className="text-gray-500 text-[11px]">
                                                                        Variante: {item.variant_name}
                                                                    </p>
                                                                )}
                                                            </TableCell>
                                                            <TableCell className="font-mono font-bold">
                                                                x{item.quantity}
                                                            </TableCell>
                                                            <TableCell>
                                                                ${parseFloat(item.unit_price_usd || "0").toFixed(2)}
                                                            </TableCell>
                                                            <TableCell className="text-right font-bold text-emerald-600">
                                                                ${parseFloat(item.total_usd || "0").toFixed(2)}
                                                            </TableCell>
                                                        </TableRow>
                                                    ))
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>

                                {/* Sección de Disputa / Auditoría */}
                                {selectedOrder.metadata?.dispute_resolution && (
                                    <div className="p-3 bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800 rounded-lg text-xs space-y-1">
                                        <div className="flex items-center gap-1.5 font-bold text-purple-900 dark:text-purple-200">
                                            <LuShieldAlert className="text-purple-600" />
                                            Resolución de Disputa Registrada
                                        </div>
                                        <p className="text-purple-800 dark:text-purple-300">
                                            <strong>Tipo:</strong> {selectedOrder.metadata.dispute_resolution.resolution_type} •{" "}
                                            <strong>Motivo:</strong> {selectedOrder.metadata.dispute_resolution.reason}
                                        </p>
                                        <p className="text-purple-600 dark:text-purple-400 text-[11px]">
                                            Resuelto el: {new Date(selectedOrder.metadata.dispute_resolution.resolved_at).toLocaleString("es-VE")}
                                        </p>
                                    </div>
                                )}
                            </>
                        )}
                    </ModalBody>
                    <ModalFooter className="flex justify-between">
                        <Button color="gray" onClick={() => setDetailModalOpen(false)}>
                            Cerrar
                        </Button>

                        <div className="flex gap-2">
                            {selectedOrder && selectedOrder.payment_status === "pending" && (
                                <Button color="success" onClick={handleConfirmPayment} disabled={confirmingPayment}>
                                    {confirmingPayment ? "Confirmando..." : "Confirmar Cobro Recibido"}
                                </Button>
                            )}

                            {selectedOrder && selectedOrder.status !== "refunded" && selectedOrder.status !== "cancelled" && (
                                <Button
                                    color="failure"
                                    onClick={() => {
                                        setDisputeModalOpen(true);
                                    }}
                                >
                                    <LuShieldAlert className="w-4 h-4 mr-2" />
                                    Mediar / Reembolsar Orden
                                </Button>
                            )}
                        </div>
                    </ModalFooter>
                </Modal>

                {/* MODAL DE MEDIACIÓN / REEMBOLSO */}
                <Modal show={disputeModalOpen} onClose={() => setDisputeModalOpen(false)} size="md">
                    <ModalHeader>
                        Resolución de Conflicto & Reembolso
                    </ModalHeader>
                    <form onSubmit={handleDisputeSubmit}>
                        <ModalBody className="space-y-4">
                            <div className="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded-lg text-xs">
                                ⚠️ Esta acción cambiará el estado de la transacción a cancelada/reembolsada y anulará las comisiones asociadas a la plataforma.
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tipo de Acción <span className="text-red-500">*</span>
                                </label>
                                <Select
                                    value={disputeType}
                                    onChange={(e) => setDisputeType(e.target.value as "refund" | "cancel")}
                                >
                                    <option value="refund">Emitir Reembolso al Comprador</option>
                                    <option value="cancel">Cancelar Orden por Incumplimiento</option>
                                </Select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Motivo del Reembolso / Cancelación <span className="text-red-500">*</span>
                                </label>
                                <TextInput
                                    required
                                    placeholder="Ej: Producto dañado, no entregado, acuerdo mutuo..."
                                    value={disputeReason}
                                    onChange={(e) => setDisputeReason(e.target.value)}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Notas Administrativas
                                </label>
                                <textarea
                                    className="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    rows={2}
                                    placeholder="Comprobante de devolución bancaria, acuerdo con el comercio, etc."
                                    value={disputeNotes}
                                    onChange={(e) => setDisputeNotes(e.target.value)}
                                />
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button color="gray" onClick={() => setDisputeModalOpen(false)} disabled={submittingDispute}>
                                Cancelar
                            </Button>
                            <Button color="failure" type="submit" disabled={submittingDispute}>
                                {submittingDispute ? <Spinner size="sm" className="mr-2" /> : <HiCheckCircle className="w-4 h-4 mr-2" />}
                                Confirmar Resolución
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>
            </div>
        </Dashboard>
    );
};

export default AdminGlobalOrdersPage;
