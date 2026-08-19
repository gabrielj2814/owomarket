import React, { useEffect, useState } from 'react';
import Dashboard from '@/components/layouts/Dashboard';
import CustomerServices from '@/Services/CustomerServices';
import OrderServices, { FilterOrdersParams } from '@/Services/OrderServices';
import ProductServices from '@/Services/ProductServices';
import { ErrorsFormOrder } from '@/types/ErrorsFormOrder';
import { FormOrder, FormOrderItem } from '@/types/FormOrder';
import { Customer } from '@/types/models/Customer';
import { Order, OrderMetrics, OrderStatusType, PaymentStatusType } from '@/types/models/Order';
import { Product } from '@/types/models/Product';
import { Head, Link } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Dropdown,
    DropdownDivider,
    DropdownHeader,
    DropdownItem,
    Label,
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
    Textarea,
} from 'flowbite-react';
import {
    HiCheck,
    HiClock,
    HiCurrencyDollar,
    HiDocumentReport,
    HiEye,
    HiFilter,
    HiHome,
    HiPlus,
    HiRefresh,
    HiSearch,
    HiShoppingCart,
    HiTrash,
    HiTruck,
    HiX,
} from 'react-icons/hi';

interface OrderIndexPageProps {
    title: string;
    user_id: string;
    host: string;
    user_name: string;
}

const statusBadgeColorMap: Record<OrderStatusType, string> = {
    pending: 'warning',
    confirmed: 'info',
    processing: 'purple',
    shipped: 'indigo',
    delivered: 'success',
    cancelled: 'failure',
    refunded: 'dark',
};

const statusLabels: Record<OrderStatusType, string> = {
    pending: 'Pendiente',
    confirmed: 'Confirmado',
    processing: 'En Proceso',
    shipped: 'Enviado',
    delivered: 'Entregado',
    cancelled: 'Cancelado',
    refunded: 'Reembolsado',
};

const paymentStatusBadgeColorMap: Record<PaymentStatusType, string> = {
    pending: 'warning',
    paid: 'success',
    failed: 'failure',
    refunded: 'dark',
};

const paymentStatusLabels: Record<PaymentStatusType, string> = {
    pending: 'Pendiente',
    paid: 'Pagado',
    failed: 'Fallido',
    refunded: 'Reembolsado',
};

export default function OrderIndexPage({ title, user_id, host, user_name }: OrderIndexPageProps) {
    // State
    const [orders, setOrders] = useState<Order[]>([]);
    const [metrics, setMetrics] = useState<OrderMetrics>({
        total_orders: 0,
        pending_orders: 0,
        processing_orders: 0,
        completed_orders: 0,
        total_sales_amount: 0,
        average_order_value: 0,
    });
    const [loading, setLoading] = useState<boolean>(true);
    const [loadingAction, setLoadingAction] = useState<boolean>(false);
    const [pagination, setPagination] = useState({
        total: 0,
        current_page: 1,
        per_page: 15,
        last_page: 1,
    });

    // Filters
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const [paymentStatusFilter, setPaymentStatusFilter] = useState<string>('all');
    const [startDate, setStartDate] = useState<string>('');
    const [endDate, setEndDate] = useState<string>('');

    // Modals
    const [isCreateModalOpen, setIsCreateModalOpen] = useState<boolean>(false);
    const [isStatusModalOpen, setIsStatusModalOpen] = useState<boolean>(false);
    const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
    const [nextStatus, setNextStatus] = useState<string>('confirmed');
    const [shippingMethodInput, setShippingMethodInput] = useState<string>('');
    const [cancelReasonInput, setCancelReasonInput] = useState<string>('');

    // Customers & Products for Manual Order Creation
    const [availableCustomers, setAvailableCustomers] = useState<Customer[]>([]);
    const [availableProducts, setAvailableProducts] = useState<Product[]>([]);
    const [isLoadingOptions, setIsLoadingOptions] = useState<boolean>(false);

    // Form Order State
    const [formOrder, setFormOrder] = useState<FormOrder>({
        customer_id: '',
        payment_method: 'transfer',
        currency: 'USD',
        tax_amount: 0,
        shipping_amount: 0,
        discount_amount: 0,
        shipping_method: 'Envío Terrestre',
        notes: '',
        customer_note: '',
        items: [],
    });
    const [formErrors, setFormErrors] = useState<ErrorsFormOrder>({});

    // Toast alert state
    const [toastMessage, setToastMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const showToast = (type: 'success' | 'error', text: string) => {
        setToastMessage({ type, text });
        setTimeout(() => setToastMessage(null), 4000);
    };

    // Load Data
    const fetchOrders = async (pageNumber: number = 1) => {
        setLoading(true);
        try {
            const params: FilterOrdersParams = {
                search: searchQuery.trim() || null,
                status: statusFilter === 'all' ? null : statusFilter,
                payment_status: paymentStatusFilter === 'all' ? null : paymentStatusFilter,
                start_date: startDate || null,
                end_date: endDate || null,
                per_page: 15,
                page: pageNumber,
            };

            const response = await OrderServices.filtrar(params);
            if (response.data && response.data.code === 200 && response.data.data) {
                setOrders(response.data.data.data);
                setPagination({
                    total: response.data.data.total,
                    current_page: response.data.data.current_page,
                    per_page: response.data.data.per_page,
                    last_page: response.data.data.last_page,
                });
            }
        } catch (e) {
            showToast('error', 'Error al cargar las órdenes.');
        } finally {
            setLoading(false);
        }
    };

    const fetchMetrics = async () => {
        try {
            const response = await OrderServices.getMetrics();
            if (response.data && response.data.code === 200 && response.data.data) {
                setMetrics(response.data.data);
            }
        } catch (e) {
            console.error('Error al cargar métricas', e);
        }
    };

    useEffect(() => {
        fetchOrders(1);
        fetchMetrics();
    }, [statusFilter, paymentStatusFilter]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchOrders(1);
    };

    const handleResetFilters = () => {
        setSearchQuery('');
        setStatusFilter('all');
        setPaymentStatusFilter('all');
        setStartDate('');
        setEndDate('');
        fetchOrders(1);
    };

    // Load options for creation modal
    const handleOpenCreateModal = async () => {
        setIsCreateModalOpen(true);
        setIsLoadingOptions(true);
        setFormErrors({});
        setFormOrder({
            customer_id: '',
            payment_method: 'transfer',
            currency: 'USD',
            tax_amount: 0,
            shipping_amount: 0,
            discount_amount: 0,
            shipping_method: 'Envío Terrestre',
            notes: '',
            customer_note: '',
            items: [],
        });

        try {
            const [custRes, prodRes] = await Promise.all([
                CustomerServices.filtrar(null, true, null, null, 100, 1),
                ProductServices.filtrar({ is_visible: true, per_page: 100 }),
            ]);

            if (custRes.data && custRes.data.code === 200 && custRes.data.data?.data) {
                setAvailableCustomers(custRes.data.data.data);
                if (custRes.data.data.data.length > 0) {
                    setFormOrder((prev) => ({ ...prev, customer_id: custRes.data.data!.data[0].id }));
                }
            }

            if (prodRes.data && prodRes.data.code === 200 && prodRes.data.data) {
                setAvailableProducts(prodRes.data.data);
            }
        } catch (e) {
            showToast('error', 'Error al cargar clientes y productos.');
        } finally {
            setIsLoadingOptions(false);
        }
    };

    // Calculation Helpers for manual order form
    const addProductToOrder = (product: Product) => {
        const existingIndex = formOrder.items.findIndex((item) => item.product_id === product.id);
        if (existingIndex >= 0) {
            const updatedItems = [...formOrder.items];
            updatedItems[existingIndex].quantity += 1;
            setFormOrder({ ...formOrder, items: updatedItems });
        } else {
            const newItem: FormOrderItem = {
                product_id: product.id,
                product_name: product.name,
                sku: product.sku || 'SKU-000',
                price: Number(product.price),
                quantity: 1,
            };
            setFormOrder({ ...formOrder, items: [...formOrder.items, newItem] });
        }
    };

    const updateItemQuantity = (index: number, qty: number) => {
        if (qty <= 0) {
            const updatedItems = formOrder.items.filter((_, i) => i !== index);
            setFormOrder({ ...formOrder, items: updatedItems });
        } else {
            const updatedItems = [...formOrder.items];
            updatedItems[index].quantity = qty;
            setFormOrder({ ...formOrder, items: updatedItems });
        }
    };

    const removeItemFromOrder = (index: number) => {
        const updatedItems = formOrder.items.filter((_, i) => i !== index);
        setFormOrder({ ...formOrder, items: updatedItems });
    };

    const calculateSubtotal = (): number => {
        return formOrder.items.reduce((acc, item) => acc + item.price * item.quantity, 0);
    };

    const calculateTotal = (): number => {
        const sub = calculateSubtotal();
        const tax = Number(formOrder.tax_amount) || 0;
        const shipping = Number(formOrder.shipping_amount) || 0;
        const discount = Number(formOrder.discount_amount) || 0;
        return Math.max(0, sub + tax + shipping - discount);
    };

    // Submit Create Order
    const handleCreateOrderSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (formOrder.items.length === 0) {
            showToast('error', 'Debe agregar al menos un producto a la orden.');
            return;
        }

        setLoadingAction(true);
        setFormErrors({});

        try {
            const response = await OrderServices.create(formOrder);
            if (response.data && (response.data.code === 201 || response.data.status === 'success')) {
                showToast('success', '¡Orden de venta registrada con éxito!');
                setIsCreateModalOpen(false);
                fetchOrders(1);
                fetchMetrics();
            } else {
                if (response.data && response.data.errors) {
                    setFormErrors(response.data.errors);
                }
                showToast('error', response.data?.message || 'Error al registrar la orden.');
            }
        } catch (e: any) {
            showToast('error', 'Error de conexión con el servidor.');
        } finally {
            setLoadingAction(false);
        }
    };

    // Status Transition Flow
    const handleOpenStatusModal = (order: Order, targetStatus: string) => {
        setSelectedOrder(order);
        setNextStatus(targetStatus);
        setShippingMethodInput(order.shipping_method || 'Chilexpress');
        setCancelReasonInput('');
        setIsStatusModalOpen(true);
    };

    const handleExecuteStatusTransition = async () => {
        if (!selectedOrder) return;
        setLoadingAction(true);

        try {
            let response;
            if (nextStatus === 'cancelled') {
                response = await OrderServices.cancel(selectedOrder.id, cancelReasonInput);
            } else {
                response = await OrderServices.updateStatus(
                    selectedOrder.id,
                    nextStatus,
                    nextStatus === 'shipped' ? shippingMethodInput : null,
                    cancelReasonInput
                );
            }

            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                showToast('success', `Estado actualizado a "${statusLabels[nextStatus as OrderStatusType]}".`);
                setIsStatusModalOpen(false);
                fetchOrders(pagination.current_page);
                fetchMetrics();
            } else {
                showToast('error', response.data?.message || 'Error al actualizar estado.');
            }
        } catch (e) {
            showToast('error', 'Error al procesar la transición de estado.');
        } finally {
            setLoadingAction(false);
        }
    };

    // Quick Mark as Paid
    const handleMarkAsPaid = async (order: Order) => {
        if (!confirm(`¿Marcar la orden ${order.order_number} como PAGADA?`)) return;
        setLoadingAction(true);
        try {
            const response = await OrderServices.updatePaymentStatus(order.id, 'paid');
            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                showToast('success', `Pago confirmado para la orden ${order.order_number}.`);
                fetchOrders(pagination.current_page);
                fetchMetrics();
            } else {
                showToast('error', response.data?.message || 'Error al actualizar pago.');
            }
        } catch (e) {
            showToast('error', 'Error de comunicación.');
        } finally {
            setLoadingAction(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />
            <div className="p-4 sm:p-6 space-y-6 max-w-7xl mx-auto">
                {/* Toast notification */}
                {toastMessage && (
                    <div
                        className={`fixed top-5 right-5 z-50 flex items-center p-4 mb-4 text-sm rounded-lg shadow-lg ${
                            toastMessage.type === 'success'
                                ? 'text-green-800 bg-green-100 dark:bg-green-800 dark:text-green-200 border border-green-300'
                                : 'text-red-800 bg-red-100 dark:bg-red-800 dark:text-red-200 border border-red-300'
                        }`}
                        role="alert"
                    >
                        <span className="font-medium mr-2">
                            {toastMessage.type === 'success' ? 'Éxito:' : 'Error:'}
                        </span>
                        {toastMessage.text}
                    </div>
                )}

                {/* Breadcrumbs */}
                <Breadcrumb>
                    <BreadcrumbItem href={`/tenant/backoffice/${user_id}/dashboard`} icon={HiHome}>
                        Dashboard
                    </BreadcrumbItem>
                    <BreadcrumbItem>
                        Pedidos & Ventas
                    </BreadcrumbItem>
                </Breadcrumb>

                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                            <HiShoppingCart className="text-blue-600 dark:text-blue-400" />
                            Gestión de Pedidos y Ventas
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Control de pipeline de pedidos, despachos, cobros y facturación de clientes.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            color="gray"
                            size="sm"
                            onClick={() => {
                                fetchOrders(pagination.current_page);
                                fetchMetrics();
                            }}
                            disabled={loading}
                        >
                            <HiRefresh className={`mr-2 h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                        <Button
                            color="blue"
                            size="sm"
                            onClick={handleOpenCreateModal}
                        >
                            <HiPlus className="mr-2 h-4 w-4" />
                            Nuevo Pedido Manual
                        </Button>
                    </div>
                </div>

                {/* KPI Metrics Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <Card className="bg-gradient-to-br from-blue-50 to-white dark:from-gray-800 dark:to-gray-900 border-blue-100 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">
                                    Ventas Totales
                                </p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    ${metrics.total_sales_amount.toLocaleString('en-US', { minimumFractionDigits: 2 })}
                                </h3>
                            </div>
                            <div className="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-xl text-blue-600 dark:text-blue-400">
                                <HiCurrencyDollar className="h-6 w-6" />
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-gradient-to-br from-indigo-50 to-white dark:from-gray-800 dark:to-gray-900 border-indigo-100 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                                    Total Órdenes
                                </p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    {metrics.total_orders}
                                </h3>
                            </div>
                            <div className="p-3 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl text-indigo-600 dark:text-indigo-400">
                                <HiShoppingCart className="h-6 w-6" />
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-gradient-to-br from-amber-50 to-white dark:from-gray-800 dark:to-gray-900 border-amber-100 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                                    Pendientes
                                </p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    {metrics.pending_orders}
                                </h3>
                            </div>
                            <div className="p-3 bg-amber-100 dark:bg-amber-900/40 rounded-xl text-amber-600 dark:text-amber-400">
                                <HiClock className="h-6 w-6" />
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-gradient-to-br from-purple-50 to-white dark:from-gray-800 dark:to-gray-900 border-purple-100 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wider">
                                    En Proceso
                                </p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    {metrics.processing_orders}
                                </h3>
                            </div>
                            <div className="p-3 bg-purple-100 dark:bg-purple-900/40 rounded-xl text-purple-600 dark:text-purple-400">
                                <HiTruck className="h-6 w-6" />
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-gradient-to-br from-emerald-50 to-white dark:from-gray-800 dark:to-gray-900 border-emerald-100 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                    Ticket Promedio
                                </p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                    ${metrics.average_order_value.toLocaleString('en-US', { minimumFractionDigits: 2 })}
                                </h3>
                            </div>
                            <div className="p-3 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl text-emerald-600 dark:text-emerald-400">
                                <HiDocumentReport className="h-6 w-6" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Filter and Search Bar */}
                <Card className="shadow-sm">
                    <div className="space-y-4">
                        {/* Status Pipeline Tabs */}
                        <div className="flex overflow-x-auto gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                            {[
                                { id: 'all', label: 'Todas las Órdenes' },
                                { id: 'pending', label: 'Pendientes' },
                                { id: 'confirmed', label: 'Confirmadas' },
                                { id: 'processing', label: 'En Proceso' },
                                { id: 'shipped', label: 'Enviadas' },
                                { id: 'delivered', label: 'Entregadas' },
                                { id: 'cancelled', label: 'Canceladas' },
                            ].map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setStatusFilter(tab.id)}
                                    className={`px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all ${
                                        statusFilter === tab.id
                                            ? 'bg-blue-600 text-white shadow-sm'
                                            : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>

                        {/* Search & Extra Filters */}
                        <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div className="relative">
                                <TextInput
                                    icon={HiSearch}
                                    placeholder="Buscar por N° orden, cliente o email..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    sizing="sm"
                                />
                            </div>

                            <div>
                                <Select
                                    value={paymentStatusFilter}
                                    onChange={(e) => setPaymentStatusFilter(e.target.value)}
                                    sizing="sm"
                                >
                                    <option value="all">Estado Pago: Todos</option>
                                    <option value="pending">Pago: Pendiente</option>
                                    <option value="paid">Pago: Pagado</option>
                                    <option value="failed">Pago: Fallido</option>
                                    <option value="refunded">Pago: Reembolsado</option>
                                </Select>
                            </div>

                            <div className="flex gap-2">
                                <TextInput
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                    sizing="sm"
                                />
                                <TextInput
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                    sizing="sm"
                                />
                            </div>

                            <div className="flex gap-2 justify-end">
                                <Button type="submit" size="sm" color="blue">
                                    <HiFilter className="mr-1 h-4 w-4" />
                                    Filtrar
                                </Button>
                                <Button type="button" size="sm" color="gray" onClick={handleResetFilters}>
                                    Limpiar
                                </Button>
                            </div>
                        </form>
                    </div>
                </Card>

                {/* Orders Table */}
                <Card className="shadow-sm overflow-hidden">
                    {loading ? (
                        <div className="flex flex-col items-center justify-center py-16">
                            <Spinner size="xl" />
                            <p className="mt-4 text-sm text-gray-500">Cargando pedidos...</p>
                        </div>
                    ) : orders.length === 0 ? (
                        <div className="text-center py-16">
                            <HiShoppingCart className="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600" />
                            <h3 className="mt-3 text-lg font-semibold text-gray-900 dark:text-white">
                                No se encontraron pedidos
                            </h3>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                No hay órdenes que coincidan con los criterios de búsqueda actuales.
                            </p>
                            <Button
                                className="mt-4 mx-auto"
                                size="sm"
                                color="blue"
                                onClick={handleOpenCreateModal}
                            >
                                <HiPlus className="mr-2 h-4 w-4" />
                                Crear Primera Orden
                            </Button>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table hoverable>
                                <TableHead className="bg-gray-50 dark:bg-gray-800 text-xs uppercase font-bold text-gray-700 dark:text-gray-300">
                                    <TableHeadCell>Correlativo</TableHeadCell>
                                    <TableHeadCell>Cliente</TableHeadCell>
                                    <TableHeadCell>Ítems</TableHeadCell>
                                    <TableHeadCell>Total</TableHeadCell>
                                    <TableHeadCell>Estado Pago</TableHeadCell>
                                    <TableHeadCell>Estado Pedido</TableHeadCell>
                                    <TableHeadCell>Fecha</TableHeadCell>
                                    <TableHeadCell className="text-right">Acciones</TableHeadCell>
                                </TableHead>
                                <TableBody className="divide-y">
                                    {orders.map((order) => (
                                        <TableRow
                                            key={order.id}
                                            className="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                        >
                                            <TableCell className="font-bold text-gray-900 dark:text-white">
                                                <Link
                                                    href={`/order/backoffice/${user_id}/show/${order.id}`}
                                                    className="text-blue-600 dark:text-blue-400 hover:underline font-mono"
                                                >
                                                    {order.order_number}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                                    {order.customer ? order.customer.name : 'Cliente ID: ' + order.customer_id.substring(0, 8)}
                                                </div>
                                                {order.customer?.email && (
                                                    <div className="text-xs text-gray-500 dark:text-gray-400">
                                                        {order.customer.email}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <span className="text-xs font-semibold bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                                    {order.items?.length || 0} {order.items?.length === 1 ? 'producto' : 'productos'}
                                                </span>
                                            </TableCell>
                                            <TableCell className="font-bold text-gray-900 dark:text-white">
                                                ${Number(order.total).toFixed(2)} {order.currency}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    color={paymentStatusBadgeColorMap[order.payment_status] || 'gray'}
                                                    className="inline-block"
                                                >
                                                    {paymentStatusLabels[order.payment_status] || order.payment_status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    color={statusBadgeColorMap[order.status] || 'gray'}
                                                    className="inline-block"
                                                >
                                                    {statusLabels[order.status] || order.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-xs text-gray-500 dark:text-gray-400">
                                                {order.created_at ? new Date(order.created_at).toLocaleDateString() : '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Link
                                                        href={`/order/backoffice/${user_id}/show/${order.id}`}
                                                        className="p-1.5 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white"
                                                        title="Ver Ficha 360°"
                                                    >
                                                        <HiEye className="h-4 w-4" />
                                                    </Link>

                                                    <Dropdown
                                                        label=""
                                                        dismissOnClick={true}
                                                        renderTrigger={() => (
                                                            <Button size="xs" color="gray">
                                                                Opciones
                                                            </Button>
                                                        )}
                                                    >
                                                        <DropdownHeader>
                                                            <span className="block text-xs font-semibold">
                                                                Orden {order.order_number}
                                                            </span>
                                                        </DropdownHeader>
                                                        {order.status === 'pending' && (
                                                            <DropdownItem
                                                                onClick={() => handleOpenStatusModal(order, 'confirmed')}
                                                            >
                                                                Confirmar Pedido
                                                            </DropdownItem>
                                                        )}
                                                        {(order.status === 'confirmed' || order.status === 'pending') && (
                                                            <DropdownItem
                                                                onClick={() => handleOpenStatusModal(order, 'processing')}
                                                            >
                                                                Iniciar Procesamiento
                                                            </DropdownItem>
                                                        )}
                                                        {order.status === 'processing' && (
                                                            <DropdownItem
                                                                onClick={() => handleOpenStatusModal(order, 'shipped')}
                                                            >
                                                                Marcar como Enviado
                                                            </DropdownItem>
                                                        )}
                                                        {order.status === 'shipped' && (
                                                            <DropdownItem
                                                                onClick={() => handleOpenStatusModal(order, 'delivered')}
                                                            >
                                                                Marcar como Entregado
                                                            </DropdownItem>
                                                        )}
                                                        {order.payment_status === 'pending' && (
                                                            <DropdownItem
                                                                onClick={() => handleMarkAsPaid(order)}
                                                            >
                                                                Marcar como Pagado
                                                            </DropdownItem>
                                                        )}
                                                        <DropdownDivider />
                                                        {order.status !== 'cancelled' && order.status !== 'delivered' && (
                                                            <DropdownItem
                                                                onClick={() => handleOpenStatusModal(order, 'cancelled')}
                                                                className="text-red-600"
                                                            >
                                                                Anular Orden
                                                            </DropdownItem>
                                                        )}
                                                    </Dropdown>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}

                    {/* Pagination */}
                    {pagination.last_page > 1 && (
                        <div className="flex justify-between items-center p-4 border-t border-gray-200 dark:border-gray-700">
                            <p className="text-xs text-gray-500">
                                Mostrando página {pagination.current_page} de {pagination.last_page} ({pagination.total} pedidos totales)
                            </p>
                            <Pagination
                                currentPage={pagination.current_page}
                                totalPages={pagination.last_page}
                                onPageChange={(p) => fetchOrders(p)}
                                showIcons
                            />
                        </div>
                    )}
                </Card>
            </div>

            {/* Modal: Crear Nuevo Pedido Manual */}
            <Modal
                show={isCreateModalOpen}
                onClose={() => setIsCreateModalOpen(false)}
                size="4xl"
            >
                <ModalHeader>Crear Nueva Orden de Venta</ModalHeader>
                <ModalBody>
                    {isLoadingOptions ? (
                        <div className="flex justify-center py-10">
                            <Spinner size="lg" />
                        </div>
                    ) : (
                        <form onSubmit={handleCreateOrderSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="customer_id">Cliente (*)</Label>
                                    <Select
                                        id="customer_id"
                                        value={formOrder.customer_id}
                                        onChange={(e) =>
                                            setFormOrder({ ...formOrder, customer_id: e.target.value })
                                        }
                                        required
                                    >
                                        <option value="">Seleccione un cliente...</option>
                                        {availableCustomers.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name} ({c.email})
                                            </option>
                                        ))}
                                    </Select>
                                    {formErrors.customer_id && (
                                        <p className="text-xs text-red-600 mt-1">{formErrors.customer_id[0]}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="payment_method">Método de Pago (*)</Label>
                                    <Select
                                        id="payment_method"
                                        value={formOrder.payment_method}
                                        onChange={(e) =>
                                            setFormOrder({ ...formOrder, payment_method: e.target.value })
                                        }
                                        required
                                    >
                                        <option value="transfer">Transferencia Bancaria</option>
                                        <option value="cash">Efectivo / Contraentrega</option>
                                        <option value="webpay">Webpay / Débito</option>
                                        <option value="credit_card">Tarjeta de Crédito</option>
                                        <option value="stripe">Stripe / Online</option>
                                    </Select>
                                </div>
                            </div>

                            {/* Product Selector Section */}
                            <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl space-y-3">
                                <Label className="font-bold text-gray-800 dark:text-gray-200">
                                    Agregar Productos al Pedido
                                </Label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-1">
                                    {availableProducts.map((prod) => (
                                        <button
                                            key={prod.id}
                                            type="button"
                                            onClick={() => addProductToOrder(prod)}
                                            className="flex flex-col text-left p-2.5 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400 transition"
                                        >
                                            <span className="text-xs font-bold text-gray-900 dark:text-white truncate">
                                                {prod.name}
                                            </span>
                                            <span className="text-xs text-blue-600 dark:text-blue-400 font-semibold mt-1">
                                                ${Number(prod.price).toFixed(2)} USD
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Added Items List */}
                            <div>
                                <Label className="font-bold mb-2 block">Ítems Seleccionados</Label>
                                {formOrder.items.length === 0 ? (
                                    <p className="text-xs text-gray-500 italic">No ha agregado productos aún.</p>
                                ) : (
                                    <div className="space-y-2">
                                        {formOrder.items.map((item, idx) => (
                                            <div
                                                key={idx}
                                                className="flex items-center justify-between p-3 bg-white dark:bg-gray-800 border rounded-lg"
                                            >
                                                <div className="flex-1">
                                                    <p className="text-sm font-bold text-gray-800 dark:text-white">
                                                        {item.product_name}
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        SKU: {item.sku} | ${item.price.toFixed(2)} c/u
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <div className="flex items-center border rounded">
                                                        <button
                                                            type="button"
                                                            className="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-xs font-bold"
                                                            onClick={() => updateItemQuantity(idx, item.quantity - 1)}
                                                        >
                                                            -
                                                        </button>
                                                        <span className="px-3 py-1 text-xs font-semibold">
                                                            {item.quantity}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            className="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-xs font-bold"
                                                            onClick={() => updateItemQuantity(idx, item.quantity + 1)}
                                                        >
                                                            +
                                                        </button>
                                                    </div>
                                                    <span className="text-sm font-bold w-20 text-right">
                                                        ${(item.price * item.quantity).toFixed(2)}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        onClick={() => removeItemFromOrder(idx)}
                                                        className="text-red-500 hover:text-red-700"
                                                    >
                                                        <HiTrash className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Taxes, Shipping, Discount & Totals Summary */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                                <div className="space-y-3">
                                    <div>
                                        <Label htmlFor="shipping_amount">Costo de Envío ($)</Label>
                                        <TextInput
                                            id="shipping_amount"
                                            type="number"
                                            step="0.01"
                                            value={formOrder.shipping_amount}
                                            onChange={(e) =>
                                                setFormOrder({
                                                    ...formOrder,
                                                    shipping_amount: parseFloat(e.target.value) || 0,
                                                })
                                            }
                                            sizing="sm"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="tax_amount">Impuestos / IVA ($)</Label>
                                        <TextInput
                                            id="tax_amount"
                                            type="number"
                                            step="0.01"
                                            value={formOrder.tax_amount}
                                            onChange={(e) =>
                                                setFormOrder({
                                                    ...formOrder,
                                                    tax_amount: parseFloat(e.target.value) || 0,
                                                })
                                            }
                                            sizing="sm"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="discount_amount">Descuento Aplicado ($)</Label>
                                        <TextInput
                                            id="discount_amount"
                                            type="number"
                                            step="0.01"
                                            value={formOrder.discount_amount}
                                            onChange={(e) =>
                                                setFormOrder({
                                                    ...formOrder,
                                                    discount_amount: parseFloat(e.target.value) || 0,
                                                })
                                            }
                                            sizing="sm"
                                        />
                                    </div>
                                </div>

                                <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl space-y-2 flex flex-col justify-center">
                                    <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                        <span>Subtotal Ítems:</span>
                                        <span className="font-semibold">${calculateSubtotal().toFixed(2)}</span>
                                    </div>
                                    <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                        <span>Envío:</span>
                                        <span className="font-semibold">${(Number(formOrder.shipping_amount) || 0).toFixed(2)}</span>
                                    </div>
                                    <div className="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                                        <span>Impuestos:</span>
                                        <span className="font-semibold">${(Number(formOrder.tax_amount) || 0).toFixed(2)}</span>
                                    </div>
                                    <div className="flex justify-between text-sm text-red-500">
                                        <span>Descuento:</span>
                                        <span className="font-semibold">-${(Number(formOrder.discount_amount) || 0).toFixed(2)}</span>
                                    </div>
                                    <div className="border-t pt-2 mt-2 flex justify-between text-lg font-extrabold text-gray-900 dark:text-white">
                                        <span>Total Final:</span>
                                        <span className="text-blue-600 dark:text-blue-400">
                                            ${calculateTotal().toFixed(2)} USD
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="notes">Notas Internas de la Orden</Label>
                                <Textarea
                                    id="notes"
                                    value={formOrder.notes}
                                    onChange={(e) => setFormOrder({ ...formOrder, notes: e.target.value })}
                                    rows={2}
                                    placeholder="Observaciones de despacho o embalaje..."
                                />
                            </div>

                            <ModalFooter className="flex justify-end gap-2 p-0 pt-4">
                                <Button color="gray" onClick={() => setIsCreateModalOpen(false)}>
                                    Cancelar
                                </Button>
                                <Button
                                    type="submit"
                                    color="blue"
                                    disabled={loadingAction}
                                >
                                    {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                                    Crear Orden
                                </Button>
                            </ModalFooter>
                        </form>
                    )}
                </ModalBody>
            </Modal>

            {/* Modal: Cambiar Estado / Anular Orden */}
            <Modal
                show={isStatusModalOpen}
                onClose={() => setIsStatusModalOpen(false)}
                size="md"
            >
                <ModalHeader>
                    {nextStatus === 'cancelled' ? 'Anular Orden de Venta' : 'Actualizar Estado del Pedido'}
                </ModalHeader>
                <ModalBody className="space-y-4">
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        ¿Desea cambiar el estado de la orden{' '}
                        <strong className="text-gray-900 dark:text-white font-mono">
                            {selectedOrder?.order_number}
                        </strong>{' '}
                        a <Badge color={statusBadgeColorMap[nextStatus as OrderStatusType] || 'info'} className="inline-block mx-1">
                            {statusLabels[nextStatus as OrderStatusType] || nextStatus}
                        </Badge>?
                    </p>

                    {nextStatus === 'shipped' && (
                        <div>
                            <Label htmlFor="shipping_method_input">Empresa de Transporte / Courier</Label>
                            <TextInput
                                id="shipping_method_input"
                                value={shippingMethodInput}
                                onChange={(e) => setShippingMethodInput(e.target.value)}
                                placeholder="Ej. Chilexpress, Starken, Blue Express..."
                            />
                        </div>
                    )}

                    {nextStatus === 'cancelled' && (
                        <div>
                            <Label htmlFor="cancel_reason">Motivo de la Anulación (*)</Label>
                            <Textarea
                                id="cancel_reason"
                                value={cancelReasonInput}
                                onChange={(e) => setCancelReasonInput(e.target.value)}
                                placeholder="Indique la razón por la cual se anula este pedido..."
                                rows={3}
                                required
                            />
                        </div>
                    )}
                </ModalBody>
                <ModalFooter className="flex justify-end gap-2">
                    <Button color="gray" onClick={() => setIsStatusModalOpen(false)}>
                        Cancelar
                    </Button>
                    <Button
                        color={nextStatus === 'cancelled' ? 'failure' : 'blue'}
                        onClick={handleExecuteStatusTransition}
                        disabled={loadingAction}
                    >
                        {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                        Confirmar Cambio
                    </Button>
                </ModalFooter>
            </Modal>
        </Dashboard>
    );
}
