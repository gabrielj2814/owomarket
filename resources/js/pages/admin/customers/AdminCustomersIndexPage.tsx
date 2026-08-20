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
    Pagination,
    Select,
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
import React, { FC, useEffect, useState } from "react";
import {
    HiCheckCircle,
    HiEye,
    HiHome,
    HiLockClosed,
    HiLockOpen,
    HiMail,
    HiPhone,
    HiRefresh,
    HiSearch,
    HiShoppingBag,
    HiUserCircle,
    HiUsers,
    HiXCircle,
} from "react-icons/hi";
import { LuBuilding2, LuLifeBuoy, LuMapPin, LuUserCheck, LuUserX } from "react-icons/lu";

interface CustomerAddress {
    id: string;
    recipient_name: string;
    phone: string;
    state: string;
    city: string;
    address_line: string;
    is_default: boolean;
}

interface CustomerModel {
    id: string;
    name: string;
    email: string;
    phone?: string | null;
    national_id?: string | null;
    is_active: boolean;
    is_verified?: boolean;
    created_at: string;
    orders_count?: number;
    support_tickets_count?: number;
    addresses?: CustomerAddress[];
    notes?: string;
}

interface Metrics {
    total_customers: number;
    active_customers: number;
    blocked_customers: number;
    customers_with_orders: number;
}

interface PaginationData {
    data: CustomerModel[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface AdminCustomersIndexPageProps {
    title?: string;
    user_id: string;
    customers_data: PaginationData;
    metrics: Metrics;
    filters: {
        search: string;
        is_active: string;
    };
}

const AdminCustomersIndexPage: FC<AdminCustomersIndexPageProps> = ({
    title = "Directorio Central de Clientes - OwOMarket",
    user_id,
    customers_data: initialPagination,
    metrics: initialMetrics,
    filters: initialFilters,
}) => {
    const [customers, setCustomers] = useState<CustomerModel[]>(initialPagination.data || []);
    const [pagination, setPagination] = useState({
        current_page: initialPagination.current_page || 1,
        last_page: initialPagination.last_page || 1,
        total: initialPagination.total || 0,
        per_page: initialPagination.per_page || 15,
    });
    const [metrics, setMetrics] = useState<Metrics>(initialMetrics);

    const [search, setSearch] = useState(initialFilters.search || "");
    const [statusFilter, setStatusFilter] = useState(initialFilters.is_active || "");
    const [loading, setLoading] = useState(false);
    const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null);

    // Modal de Detalle 360 del Cliente
    const [detailModalOpen, setDetailModalOpen] = useState(false);
    const [selectedCustomerDetail, setSelectedCustomerDetail] = useState<any>(null);
    const [loadingDetail, setLoadingDetail] = useState(false);

    // Modal de Bloqueo / Desbloqueo
    const [toggleModalOpen, setToggleModalOpen] = useState(false);
    const [customerToToggle, setCustomerToToggle] = useState<CustomerModel | null>(null);
    const [toggleReason, setToggleReason] = useState("");
    const [toggling, setToggling] = useState(false);

    const fetchCustomers = async (page = 1) => {
        setLoading(true);
        try {
            const params: any = { page };
            if (search.trim()) params.search = search.trim();
            if (statusFilter !== "") params.is_active = statusFilter;

            const response = await axios.get("/admin/api/customers", { params });
            if (response.data?.status === "success") {
                const resData = response.data.data;
                setCustomers(resData.customers.data);
                setPagination({
                    current_page: resData.customers.current_page,
                    last_page: resData.customers.last_page,
                    total: resData.customers.total,
                    per_page: resData.customers.per_page,
                });
                setMetrics(resData.metrics);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al cargar clientes." });
        } finally {
            setLoading(false);
        }
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchCustomers(1);
    };

    const handleOpenDetail = async (customer: CustomerModel) => {
        setDetailModalOpen(true);
        setLoadingDetail(true);
        setSelectedCustomerDetail(null);
        try {
            const response = await axios.get(`/admin/api/customers/${customer.id}/detail`);
            if (response.data?.status === "success") {
                setSelectedCustomerDetail(response.data.data);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al cargar expediente del cliente." });
        } finally {
            setLoadingDetail(false);
        }
    };

    const handleToggleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!customerToToggle) return;

        setToggling(true);
        try {
            const response = await axios.patch(`/admin/api/customers/${customerToToggle.id}/toggle-status`, {
                reason: toggleReason.trim() || undefined,
            });

            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: response.data.message || "Estado del cliente actualizado.",
                });
                setToggleModalOpen(false);
                setToggleReason("");
                fetchCustomers(pagination.current_page);
                if (selectedCustomerDetail && selectedCustomerDetail.customer.id === customerToToggle.id) {
                    handleOpenDetail(customerToToggle);
                }
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al actualizar estado del cliente.",
            });
        } finally {
            setToggling(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />
            <div className="p-4 sm:p-6 space-y-6 max-w-7xl mx-auto">
                {/* Header & Breadcrumb */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <Breadcrumb className="mb-2">
                            <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                                Panel Global
                            </BreadcrumbItem>
                            <BreadcrumbItem>Directorio de Clientes</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiUsers className="text-blue-600 w-8 h-8" />
                            Directorio Central de Clientes
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Auditoría, expediente de compras y control de seguridad de los compradores del marketplace.
                        </p>
                    </div>

                    <Button
                        color="light"
                        size="sm"
                        onClick={() => fetchCustomers(pagination.current_page)}
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
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-l-4 border-blue-600 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Total Compradores
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_customers || 0}
                                </h3>
                                <p className="text-xs text-blue-600 font-medium mt-1">
                                    Base central del marketplace
                                </p>
                            </div>
                            <div className="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-xl">
                                <HiUsers className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Clientes Activos
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.active_customers || 0}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    Habilitados para compras
                                </p>
                            </div>
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-xl">
                                <LuUserCheck className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-purple-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Con Compras Realizadas
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.customers_with_orders || 0}
                                </h3>
                                <p className="text-xs text-purple-600 font-medium mt-1">
                                    Clientes convertidos
                                </p>
                            </div>
                            <div className="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 rounded-xl">
                                <HiShoppingBag className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-rose-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Cuentas Bloqueadas
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.blocked_customers || 0}
                                </h3>
                                <p className="text-xs text-rose-600 font-medium mt-1">
                                    Por seguridad / disputas
                                </p>
                            </div>
                            <div className="p-3 bg-rose-50 dark:bg-rose-900/30 text-rose-600 rounded-xl">
                                <LuUserX className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* FILTROS & TABLA */}
                <Card className="shadow-sm">
                    {/* Barra de Filtros */}
                    <form onSubmit={handleSearchSubmit} className="flex flex-col md:flex-row items-center gap-3 mb-4">
                        <div className="relative flex-1 w-full">
                            <TextInput
                                icon={HiSearch}
                                placeholder="Buscar por nombre, correo, teléfono o cédula..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        <div className="w-full md:w-48">
                            <Select
                                value={statusFilter}
                                onChange={(e) => {
                                    setStatusFilter(e.target.value);
                                }}
                            >
                                <option value="">Todos los Estados</option>
                                <option value="1">Solo Activos</option>
                                <option value="0">Solo Bloqueados</option>
                            </Select>
                        </div>
                        <Button type="submit" color="blue" disabled={loading} className="w-full md:w-auto">
                            Filtrar
                        </Button>
                    </form>

                    {/* Tabla */}
                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                <TableHeadCell>Comprador</TableHeadCell>
                                <TableHeadCell>Contacto</TableHeadCell>
                                <TableHeadCell>Cédula / DNI</TableHeadCell>
                                <TableHeadCell>Órdenes</TableHeadCell>
                                <TableHeadCell>Tickets</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell>Registrado</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y text-xs">
                                {customers.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-8 text-gray-400">
                                            No se encontraron clientes registrados con los filtros aplicados.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    customers.map((c) => (
                                        <TableRow key={c.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <div className="w-9 h-9 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm flex-shrink-0">
                                                        {c.name.substring(0, 1).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <p className="font-bold text-gray-900 dark:text-white">
                                                            {c.name}
                                                        </p>
                                                        <p className="text-[11px] text-gray-400 font-mono">
                                                            ID: {c.id.substring(0, 8)}...
                                                        </p>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-0.5">
                                                    <p className="font-mono text-gray-700 dark:text-gray-300">
                                                        {c.email}
                                                    </p>
                                                    <p className="text-gray-400">{c.phone || "Sin teléfono"}</p>
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-mono font-medium">
                                                {c.national_id || "-"}
                                            </TableCell>
                                            <TableCell>
                                                <Badge color="purple" className="w-fit">
                                                    {c.orders_count || 0} compras
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge color="gray" className="w-fit">
                                                    {c.support_tickets_count || 0} tickets
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    color={c.is_active ? "success" : "failure"}
                                                    className="capitalize w-fit"
                                                >
                                                    {c.is_active ? "Activo" : "Bloqueado"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-gray-500">
                                                {new Date(c.created_at).toLocaleDateString("es-VE")}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button
                                                        size="xs"
                                                        color="light"
                                                        onClick={() => handleOpenDetail(c)}
                                                        title="Ver Expediente 360°"
                                                    >
                                                        <HiEye className="w-4 h-4 text-blue-600" />
                                                    </Button>
                                                    <Button
                                                        size="xs"
                                                        color={c.is_active ? "failure" : "success"}
                                                        onClick={() => {
                                                            setCustomerToToggle(c);
                                                            setToggleModalOpen(true);
                                                        }}
                                                        title={c.is_active ? "Bloquear Cliente" : "Desbloquear Cliente"}
                                                    >
                                                        {c.is_active ? (
                                                            <HiLockClosed className="w-4 h-4" />
                                                        ) : (
                                                            <HiLockOpen className="w-4 h-4" />
                                                        )}
                                                    </Button>
                                                </div>
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
                                onPageChange={(page) => fetchCustomers(page)}
                                showIcons
                                previousLabel="Anterior"
                                nextLabel="Siguiente"
                            />
                        </div>
                    )}
                </Card>

                {/* MODAL DE DETALLE 360° DEL CLIENTE */}
                <Modal show={detailModalOpen} onClose={() => setDetailModalOpen(false)} size="4xl">
                    <ModalHeader>
                        Expediente 360° del Comprador
                    </ModalHeader>
                    <ModalBody className="space-y-6">
                        {loadingDetail || !selectedCustomerDetail ? (
                            <div className="text-center py-12">
                                <Spinner size="xl" />
                                <p className="text-xs text-gray-500 mt-2">Cargando historial del cliente...</p>
                            </div>
                        ) : (
                            <>
                                {/* Perfil superior */}
                                <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div className="flex items-center gap-4">
                                        <div className="w-14 h-14 rounded-2xl bg-blue-600 text-white font-extrabold text-2xl flex items-center justify-center shadow">
                                            {selectedCustomerDetail.customer.name.substring(0, 1).toUpperCase()}
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                                {selectedCustomerDetail.customer.name}
                                            </h3>
                                            <p className="text-xs text-gray-500 font-mono">
                                                {selectedCustomerDetail.customer.email} • {selectedCustomerDetail.customer.phone || "Sin teléfono"}
                                            </p>
                                            <div className="flex items-center gap-2 mt-1">
                                                <Badge color={selectedCustomerDetail.customer.is_active ? "success" : "failure"}>
                                                    {selectedCustomerDetail.customer.is_active ? "Activo" : "Bloqueado"}
                                                </Badge>
                                                <span className="text-xs text-gray-400">
                                                    Miembro desde {new Date(selectedCustomerDetail.customer.created_at).toLocaleDateString("es-VE")}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-4 text-center sm:text-right border-t sm:border-t-0 pt-3 sm:pt-0 border-gray-200">
                                        <div>
                                            <p className="text-xs text-gray-500">Gasto Total Acumulado</p>
                                            <p className="text-xl font-extrabold text-emerald-600">
                                                ${selectedCustomerDetail.metrics.total_spent_usd.toFixed(2)}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Pestañas */}
                                <Tabs aria-label="Expediente Cliente" variant="underline">
                                    {/* ÓRDENES */}
                                    <TabItem active title={`Órdenes (${selectedCustomerDetail.orders.length})`} icon={HiShoppingBag}>
                                        <div className="pt-3 overflow-x-auto">
                                            <Table hoverable>
                                                <TableHead className="text-xs bg-gray-100 dark:bg-gray-700">
                                                    <TableHeadCell>N° Orden</TableHeadCell>
                                                    <TableHeadCell>Total USD</TableHeadCell>
                                                    <TableHeadCell>Pago</TableHeadCell>
                                                    <TableHeadCell>Estado</TableHeadCell>
                                                    <TableHeadCell>Fecha</TableHeadCell>
                                                </TableHead>
                                                <TableBody className="divide-y text-xs">
                                                    {selectedCustomerDetail.orders.length === 0 ? (
                                                        <TableRow>
                                                            <TableCell colSpan={5} className="text-center py-4 text-gray-400">
                                                                No hay órdenes registradas.
                                                            </TableCell>
                                                        </TableRow>
                                                    ) : (
                                                        selectedCustomerDetail.orders.map((o: any) => (
                                                            <TableRow key={o.id}>
                                                                <TableCell className="font-mono font-semibold text-blue-600">
                                                                    {o.order_number || o.id.substring(0, 8)}
                                                                </TableCell>
                                                                <TableCell className="font-bold">
                                                                    ${parseFloat(o.total_usd || "0").toFixed(2)}
                                                                </TableCell>
                                                                <TableCell>
                                                                    <Badge color={o.payment_status === "paid" ? "success" : "warning"} className="w-fit">
                                                                        {o.payment_status}
                                                                    </Badge>
                                                                </TableCell>
                                                                <TableCell>
                                                                    <Badge color="info" className="w-fit">
                                                                        {o.status}
                                                                    </Badge>
                                                                </TableCell>
                                                                <TableCell className="text-gray-500">
                                                                    {new Date(o.created_at).toLocaleDateString("es-VE")}
                                                                </TableCell>
                                                            </TableRow>
                                                        ))
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </TabItem>

                                    {/* DIRECCIONES */}
                                    <TabItem title={`Direcciones (${selectedCustomerDetail.customer.addresses?.length || 0})`} icon={LuMapPin}>
                                        <div className="pt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                                            {(!selectedCustomerDetail.customer.addresses || selectedCustomerDetail.customer.addresses.length === 0) ? (
                                                <p className="text-xs text-gray-400 italic">No hay direcciones guardadas.</p>
                                            ) : (
                                                selectedCustomerDetail.customer.addresses.map((addr: any) => (
                                                    <div key={addr.id} className="p-3 border border-gray-200 dark:border-gray-700 rounded-lg text-xs space-y-1 bg-gray-50 dark:bg-gray-800">
                                                        <div className="flex items-center justify-between">
                                                            <span className="font-bold text-gray-900 dark:text-white">
                                                                {addr.recipient_name}
                                                            </span>
                                                            {addr.is_default && <Badge color="indigo">Principal</Badge>}
                                                        </div>
                                                        <p className="text-gray-600 dark:text-gray-300">{addr.address_line}</p>
                                                        <p className="text-gray-500">{addr.city}, {addr.state} • {addr.phone}</p>
                                                    </div>
                                                ))
                                            )}
                                        </div>
                                    </TabItem>

                                    {/* TICKETS */}
                                    <TabItem title={`Tickets de Soporte (${selectedCustomerDetail.tickets.length})`} icon={LuLifeBuoy}>
                                        <div className="pt-3 overflow-x-auto">
                                            <Table hoverable>
                                                <TableHead className="text-xs bg-gray-100 dark:bg-gray-700">
                                                    <TableHeadCell>Ticket</TableHeadCell>
                                                    <TableHeadCell>Asunto</TableHeadCell>
                                                    <TableHeadCell>Estado</TableHeadCell>
                                                    <TableHeadCell>Fecha</TableHeadCell>
                                                </TableHead>
                                                <TableBody className="divide-y text-xs">
                                                    {selectedCustomerDetail.tickets.length === 0 ? (
                                                        <TableRow>
                                                            <TableCell colSpan={4} className="text-center py-4 text-gray-400">
                                                                No hay tickets de soporte reportados por este comprador.
                                                            </TableCell>
                                                        </TableRow>
                                                    ) : (
                                                        selectedCustomerDetail.tickets.map((t: any) => (
                                                            <TableRow key={t.id}>
                                                                <TableCell className="font-mono font-semibold">{t.ticket_number}</TableCell>
                                                                <TableCell className="font-medium truncate max-w-[200px]">{t.subject}</TableCell>
                                                                <TableCell>
                                                                    <Badge color={t.status === "open" ? "failure" : "success"} className="w-fit">
                                                                        {t.status}
                                                                    </Badge>
                                                                </TableCell>
                                                                <TableCell className="text-gray-500">
                                                                    {new Date(t.created_at).toLocaleDateString("es-VE")}
                                                                </TableCell>
                                                            </TableRow>
                                                        ))
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </TabItem>
                                </Tabs>
                            </>
                        )}
                    </ModalBody>
                    <ModalFooter>
                        <Button color="gray" onClick={() => setDetailModalOpen(false)}>
                            Cerrar
                        </Button>
                    </ModalFooter>
                </Modal>

                {/* MODAL CONFIRMAR BLOQUEO / DESBLOQUEO */}
                <Modal show={toggleModalOpen} onClose={() => setToggleModalOpen(false)} size="md">
                    <ModalHeader>
                        {customerToToggle?.is_active ? "Bloquear Cuenta de Cliente" : "Desbloquear Cuenta de Cliente"}
                    </ModalHeader>
                    <form onSubmit={handleToggleSubmit}>
                        <ModalBody className="space-y-4">
                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                ¿Estás seguro de que deseas {customerToToggle?.is_active ? "bloquear" : "desbloquear"} al cliente{" "}
                                <strong>{customerToToggle?.name}</strong> ({customerToToggle?.email})?
                            </p>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Motivo del cambio de estado (Opcional)
                                </label>
                                <TextInput
                                    placeholder="Ej: Actividad fraudulenta, reclamo resuelto, etc."
                                    value={toggleReason}
                                    onChange={(e) => setToggleReason(e.target.value)}
                                />
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button color="gray" onClick={() => setToggleModalOpen(false)} disabled={toggling}>
                                Cancelar
                            </Button>
                            <Button
                                color={customerToToggle?.is_active ? "failure" : "success"}
                                type="submit"
                                disabled={toggling}
                            >
                                {toggling ? <Spinner size="sm" className="mr-2" /> : null}
                                Confirmar {customerToToggle?.is_active ? "Bloqueo" : "Desbloqueo"}
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>
            </div>
        </Dashboard>
    );
};

export default AdminCustomersIndexPage;
