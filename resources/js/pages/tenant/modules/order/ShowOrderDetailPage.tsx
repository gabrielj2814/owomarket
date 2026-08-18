import React, { useEffect, useState } from 'react';
import Dashboard from '@/components/layouts/Dashboard';
import BillingServices from '@/Services/BillingServices';
import OrderServices from '@/Services/OrderServices';
import { FormDirectInvoice } from '@/types/FormDirectInvoice';
import { Order, OrderStatusType, PaymentStatusType } from '@/types/models/Order';
import { Head, Link } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Dropdown,
    DropdownItem,
    Label,
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
    Textarea,
} from 'flowbite-react';
import {
    HiArrowLeft,
    HiCheckCircle,
    HiClock,
    HiCreditCard,
    HiDocumentText,
    HiExclamation,
    HiHome,
    HiMail,
    HiPhone,
    HiPrinter,
    HiShoppingCart,
    HiTruck,
    HiUser,
} from 'react-icons/hi';

interface ShowOrderDetailPageProps {
    title: string;
    user_id: string;
    order_id: string;
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
    pending: 'Pendiente de Confirmación',
    confirmed: 'Confirmado',
    processing: 'En Preparación / Procesamiento',
    shipped: 'Enviado / En Tránsito',
    delivered: 'Entregado al Cliente',
    cancelled: 'Orden Cancelada',
    refunded: 'Reembolsado',
};

const paymentStatusBadgeColorMap: Record<PaymentStatusType, string> = {
    pending: 'warning',
    paid: 'success',
    failed: 'failure',
    refunded: 'dark',
};

export default function ShowOrderDetailPage({
    title,
    user_id,
    order_id,
    host,
    user_name,
}: ShowOrderDetailPageProps) {
    const [order, setOrder] = useState<Order | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [loadingAction, setLoadingAction] = useState<boolean>(false);
    const [toastMessage, setToastMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    // Modal state for status change
    const [isStatusModalOpen, setIsStatusModalOpen] = useState<boolean>(false);
    const [targetStatus, setTargetStatus] = useState<string>('confirmed');
    const [shippingMethodInput, setShippingMethodInput] = useState<string>('');
    const [cancelReasonInput, setCancelReasonInput] = useState<string>('');

    // State for invoice generation
    const [invoiceCreatedSuccess, setInvoiceCreatedSuccess] = useState<string | null>(null);

    const showToast = (type: 'success' | 'error', text: string) => {
        setToastMessage({ type, text });
        setTimeout(() => setToastMessage(null), 4000);
    };

    const fetchOrder = async () => {
        setLoading(true);
        try {
            const response = await OrderServices.consultById(order_id);
            if (response.data && response.data.code === 200 && response.data.data) {
                setOrder(response.data.data);
            } else {
                showToast('error', response.data?.message || 'Error al consultar la orden.');
            }
        } catch (e) {
            showToast('error', 'Error al comunicarse con el servidor.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchOrder();
    }, [order_id]);

    const handleOpenStatusModal = (status: string) => {
        setTargetStatus(status);
        setShippingMethodInput(order?.shipping_method || 'Chilexpress');
        setCancelReasonInput('');
        setIsStatusModalOpen(true);
    };

    const handleExecuteStatusTransition = async () => {
        if (!order) return;
        setLoadingAction(true);

        try {
            let response;
            if (targetStatus === 'cancelled') {
                response = await OrderServices.cancel(order.id, cancelReasonInput);
            } else {
                response = await OrderServices.updateStatus(
                    order.id,
                    targetStatus,
                    targetStatus === 'shipped' ? shippingMethodInput : null,
                    cancelReasonInput
                );
            }

            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                showToast('success', `Estado actualizado a "${statusLabels[targetStatus as OrderStatusType]}".`);
                setIsStatusModalOpen(false);
                fetchOrder();
            } else {
                showToast('error', response.data?.message || 'Error al actualizar estado.');
            }
        } catch (e) {
            showToast('error', 'Error de conexión.');
        } finally {
            setLoadingAction(false);
        }
    };

    const handleMarkAsPaid = async () => {
        if (!order) return;
        setLoadingAction(true);
        try {
            const response = await OrderServices.updatePaymentStatus(order.id, 'paid');
            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                showToast('success', 'La orden ha sido marcada como PAGADA.');
                fetchOrder();
            } else {
                showToast('error', response.data?.message || 'Error al actualizar pago.');
            }
        } catch (e) {
            showToast('error', 'Error de conexión.');
        } finally {
            setLoadingAction(false);
        }
    };

    // Direct Invoice Generation Bridge
    const handleGenerateInvoice = async () => {
        if (!order) return;
        setLoadingAction(true);

        try {
            const customerName = order.customer ? order.customer.name : 'Cliente Consumidor Final';
            const customerEmail = order.customer?.email || 'cliente@ejemplo.com';

            const invoicePayload: FormDirectInvoice = {
                customer_name: customerName,
                customer_email: customerEmail,
                customer_tax_id: '76.123.456-7',
                customer_address_line_1: 'Dirección Comercial Principal',
                customer_city: 'Santiago',
                customer_state: 'RM',
                customer_postal_code: '7500000',
                customer_country: 'Chile',
                currency: order.currency || 'USD',
                payment_method: order.payment_method || 'transfer',
                payment_status: order.payment_status || 'pending',
                status: 'issued',
                notes: `Factura emitida automáticamente desde Orden ${order.order_number}`,
                items: order.items.map((item) => ({
                    product_id: item.product_id,
                    description: item.product_name,
                    sku: item.sku,
                    quantity: item.quantity,
                    unit_price: Number(item.price),
                    tax_rate: 19.0,
                    discount_amount: 0,
                })),
            };

            const response = await BillingServices.createDirectInvoice(invoicePayload);
            if (response.data && (response.data.code === 201 || response.data.status === 'success') && response.data.data) {
                setInvoiceCreatedSuccess(response.data.data.invoice_number);
                showToast('success', `¡Factura ${response.data.data.invoice_number} generada con éxito!`);
            } else {
                showToast('error', response.data?.message || 'Error al emitir factura fiscal.');
            }
        } catch (e) {
            showToast('error', 'Error al emitir la factura fiscal.');
        } finally {
            setLoadingAction(false);
        }
    };

    const timelineSteps: { status: OrderStatusType; label: string; icon: React.ComponentType<{ className?: string }> }[] = [
        { status: 'pending', label: '1. Pedido Recibido', icon: HiClock },
        { status: 'confirmed', label: '2. Confirmado', icon: HiCheckCircle },
        { status: 'processing', label: '3. En Preparación', icon: HiShoppingCart },
        { status: 'shipped', label: '4. Despachado', icon: HiTruck },
        { status: 'delivered', label: '5. Entregado', icon: HiCheckCircle },
    ];

    const getStepState = (stepStatus: OrderStatusType) => {
        if (!order) return 'upcoming';
        if (order.status === 'cancelled') return 'cancelled';

        const statusOrder: OrderStatusType[] = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
        const currentIndex = statusOrder.indexOf(order.status);
        const stepIndex = statusOrder.indexOf(stepStatus);

        if (stepIndex <= currentIndex) return 'completed';
        return 'upcoming';
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />
            <div className="p-4 sm:p-6 space-y-6 max-w-7xl mx-auto">
                {/* Toast Notification */}
                {toastMessage && (
                    <div
                        className={`fixed top-5 right-5 z-50 flex items-center p-4 mb-4 text-sm rounded-lg shadow-lg ${
                            toastMessage.type === 'success'
                                ? 'text-green-800 bg-green-100 dark:bg-green-800 dark:text-green-200 border border-green-300'
                                : 'text-red-800 bg-red-100 dark:bg-red-800 dark:text-red-200 border border-red-300'
                        }`}
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
                    <BreadcrumbItem href={`/order/backoffice/${user_id}/module`}>
                        Pedidos
                    </BreadcrumbItem>
                    <BreadcrumbItem>
                        {order ? order.order_number : 'Cargando orden...'}
                    </BreadcrumbItem>
                </Breadcrumb>

                {loading ? (
                    <div className="flex flex-col items-center justify-center py-20">
                        <Spinner size="xl" />
                        <p className="mt-4 text-sm text-gray-500">Cargando detalles de la orden...</p>
                    </div>
                ) : !order ? (
                    <Card className="text-center py-12">
                        <HiExclamation className="mx-auto h-12 w-12 text-red-500" />
                        <h3 className="text-lg font-bold text-gray-900 mt-2">Orden no encontrada</h3>
                        <Link
                            href={`/order/backoffice/${user_id}/module`}
                            className="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold"
                        >
                            Volver a Pedidos
                        </Link>
                    </Card>
                ) : (
                    <>
                        {/* Top Header Card */}
                        <Card className="shadow-sm">
                            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div>
                                    <div className="flex items-center gap-3">
                                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white font-mono">
                                            {order.order_number}
                                        </h1>
                                        <Badge
                                            color={statusBadgeColorMap[order.status] || 'gray'}
                                            size="sm"
                                        >
                                            {statusLabels[order.status] || order.status}
                                        </Badge>
                                        <Badge
                                            color={paymentStatusBadgeColorMap[order.payment_status] || 'gray'}
                                            size="sm"
                                        >
                                            Pago: {order.payment_status.toUpperCase()}
                                        </Badge>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1">
                                        Emitida el {order.created_at ? new Date(order.created_at).toLocaleString() : '-'} | ID: {order.id}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Link
                                        href={`/order/backoffice/${user_id}/module`}
                                        className="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold flex items-center gap-1"
                                    >
                                        <HiArrowLeft className="h-4 w-4" />
                                        Volver
                                    </Link>

                                    {order.payment_status === 'pending' && (
                                        <Button
                                            color="success"
                                            size="sm"
                                            onClick={handleMarkAsPaid}
                                            disabled={loadingAction}
                                        >
                                            <HiCheckCircle className="mr-1 h-4 w-4" />
                                            Marcar Pagado
                                        </Button>
                                    )}

                                    <Button
                                        color="blue"
                                        size="sm"
                                        onClick={handleGenerateInvoice}
                                        disabled={loadingAction}
                                    >
                                        <HiDocumentText className="mr-1 h-4 w-4" />
                                        {invoiceCreatedSuccess ? `Factura: ${invoiceCreatedSuccess}` : 'Emitir Factura Fiscal'}
                                    </Button>

                                    <Dropdown
                                        label="Cambiar Estado"
                                        size="sm"
                                        color="blue"
                                        disabled={loadingAction || order.status === 'cancelled' || order.status === 'delivered'}
                                    >
                                        {order.status === 'pending' && (
                                            <DropdownItem onClick={() => handleOpenStatusModal('confirmed')}>
                                                Confirmar Pedido
                                            </DropdownItem>
                                        )}
                                        {(order.status === 'pending' || order.status === 'confirmed') && (
                                            <DropdownItem onClick={() => handleOpenStatusModal('processing')}>
                                                Iniciar Preparación
                                            </DropdownItem>
                                        )}
                                        {order.status === 'processing' && (
                                            <DropdownItem onClick={() => handleOpenStatusModal('shipped')}>
                                                Marcar como Enviado
                                            </DropdownItem>
                                        )}
                                        {order.status === 'shipped' && (
                                            <DropdownItem onClick={() => handleOpenStatusModal('delivered')}>
                                                Marcar como Entregado
                                            </DropdownItem>
                                        )}
                                        {order.status !== 'cancelled' && (
                                            <DropdownItem
                                                onClick={() => handleOpenStatusModal('cancelled')}
                                                className="text-red-600"
                                            >
                                                Anular Pedido
                                            </DropdownItem>
                                        )}
                                    </Dropdown>

                                    <Button
                                        color="gray"
                                        size="sm"
                                        onClick={() => window.print()}
                                    >
                                        <HiPrinter className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            {/* Lifecycle Stepper */}
                            <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <h4 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">
                                    Línea de Tiempo del Pedido
                                </h4>
                                <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                    {timelineSteps.map((step, idx) => {
                                        const stepState = getStepState(step.status);
                                        const StepIcon = step.icon;
                                        return (
                                            <div
                                                key={idx}
                                                className={`flex items-center gap-2 p-3 rounded-lg border text-xs font-semibold ${
                                                    stepState === 'completed'
                                                        ? 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/30 dark:border-blue-700 dark:text-blue-200'
                                                        : stepState === 'cancelled'
                                                        ? 'bg-red-50 border-red-200 text-red-700'
                                                        : 'bg-gray-50 border-gray-200 text-gray-400 dark:bg-gray-800 dark:border-gray-700'
                                                }`}
                                            >
                                                <StepIcon className="h-4 w-4 shrink-0" />
                                                <span className="truncate">{step.label}</span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </Card>

                        {/* Main Grid Content */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* Left Column (Items & Financial Summary) - 2 Cols */}
                            <div className="lg:col-span-2 space-y-6">
                                {/* Order Items Table */}
                                <Card className="shadow-sm">
                                    <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                        Ítems del Pedido ({order.items.length})
                                    </h3>
                                    <div className="overflow-x-auto">
                                        <Table hoverable>
                                            <TableHead className="bg-gray-50 text-xs font-bold uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                <TableHeadCell>Producto</TableHeadCell>
                                                <TableHeadCell>SKU</TableHeadCell>
                                                <TableHeadCell>Cant.</TableHeadCell>
                                                <TableHeadCell>Precio Unit.</TableHeadCell>
                                                <TableHeadCell className="text-right">Subtotal</TableHeadCell>
                                            </TableHead>
                                            <TableBody className="divide-y">
                                                {order.items.map((item) => (
                                                    <TableRow key={item.id} className="bg-white dark:bg-gray-800">
                                                        <TableCell className="font-bold text-gray-900 dark:text-white">
                                                            {item.product_name}
                                                        </TableCell>
                                                        <TableCell className="text-xs text-gray-500 font-mono">
                                                            {item.sku}
                                                        </TableCell>
                                                        <TableCell className="font-semibold">
                                                            {item.quantity}
                                                        </TableCell>
                                                        <TableCell>
                                                            ${Number(item.price).toFixed(2)}
                                                        </TableCell>
                                                        <TableCell className="font-bold text-right text-gray-900 dark:text-white">
                                                            ${Number(item.total).toFixed(2)}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>

                                    {/* Financial Breakdown */}
                                    <div className="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                                        <div className="w-full sm:w-72 space-y-2 text-sm">
                                            <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                                <span>Subtotal Neto:</span>
                                                <span className="font-semibold">${Number(order.subtotal).toFixed(2)}</span>
                                            </div>
                                            <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                                <span>Envío / Despacho:</span>
                                                <span className="font-semibold">${Number(order.shipping_amount).toFixed(2)}</span>
                                            </div>
                                            <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                                <span>Impuestos / IVA:</span>
                                                <span className="font-semibold">${Number(order.tax_amount).toFixed(2)}</span>
                                            </div>
                                            {Number(order.discount_amount) > 0 && (
                                                <div className="flex justify-between text-red-500">
                                                    <span>Descuento Especial:</span>
                                                    <span className="font-semibold">-${Number(order.discount_amount).toFixed(2)}</span>
                                                </div>
                                            )}
                                            <div className="border-t pt-2 flex justify-between text-lg font-black text-gray-900 dark:text-white">
                                                <span>Total a Pagar:</span>
                                                <span className="text-blue-600 dark:text-blue-400">
                                                    ${Number(order.total).toFixed(2)} {order.currency}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </Card>

                                {/* Notes Section */}
                                <Card className="shadow-sm space-y-3">
                                    <h3 className="text-md font-bold text-gray-900 dark:text-white">
                                        Observaciones y Notas
                                    </h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <span className="text-xs font-bold text-gray-500 uppercase">Nota del Cliente:</span>
                                            <p className="text-sm text-gray-800 dark:text-gray-200 mt-1 italic">
                                                {order.customer_note || 'Sin notas especiales especificadas por el cliente.'}
                                            </p>
                                        </div>
                                        <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <span className="text-xs font-bold text-gray-500 uppercase">Notas Internas:</span>
                                            <p className="text-sm text-gray-800 dark:text-gray-200 mt-1">
                                                {order.notes || 'Sin notas internas registradas.'}
                                            </p>
                                        </div>
                                    </div>
                                </Card>
                            </div>

                            {/* Right Column (Customer, Shipping, Payment) - 1 Col */}
                            <div className="space-y-6">
                                {/* Customer Profile Card */}
                                <Card className="shadow-sm">
                                    <div className="flex items-center gap-2 border-b pb-3">
                                        <HiUser className="h-5 w-5 text-blue-600" />
                                        <h3 className="font-bold text-gray-900 dark:text-white">
                                            Datos del Cliente
                                        </h3>
                                    </div>
                                    <div className="space-y-3 text-sm">
                                        <div>
                                            <span className="text-xs text-gray-500 font-semibold block">Nombre:</span>
                                            <p className="font-bold text-gray-900 dark:text-white">
                                                {order.customer ? order.customer.name : 'Cliente no registrado'}
                                            </p>
                                        </div>
                                        {order.customer?.email && (
                                            <div>
                                                <span className="text-xs text-gray-500 font-semibold block">Email:</span>
                                                <a
                                                    href={`mailto:${order.customer.email}`}
                                                    className="text-blue-600 hover:underline flex items-center gap-1"
                                                >
                                                    <HiMail className="h-4 w-4" />
                                                    {order.customer.email}
                                                </a>
                                            </div>
                                        )}
                                        {order.customer?.phone && (
                                            <div>
                                                <span className="text-xs text-gray-500 font-semibold block">Teléfono:</span>
                                                <span className="flex items-center gap-1 text-gray-800 dark:text-gray-200">
                                                    <HiPhone className="h-4 w-4" />
                                                    {order.customer.phone}
                                                </span>
                                            </div>
                                        )}
                                        {order.customer && (
                                            <div className="pt-2">
                                                <Link
                                                    href={`/customer/backoffice/${user_id}/show/${order.customer.id}`}
                                                    className="block w-full py-1.5 px-3 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded"
                                                >
                                                    Ver Historial del Cliente
                                                </Link>
                                            </div>
                                        )}
                                    </div>
                                </Card>

                                {/* Shipping Details Card */}
                                <Card className="shadow-sm">
                                    <div className="flex items-center gap-2 border-b pb-3">
                                        <HiTruck className="h-5 w-5 text-indigo-600" />
                                        <h3 className="font-bold text-gray-900 dark:text-white">
                                            Datos de Despacho
                                        </h3>
                                    </div>
                                    <div className="space-y-3 text-sm">
                                        <div>
                                            <span className="text-xs text-gray-500 font-semibold block">Método / Courier:</span>
                                            <p className="font-semibold text-gray-800 dark:text-gray-200">
                                                {order.shipping_method || 'Envío Terrestre Estándar'}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-xs text-gray-500 font-semibold block">Fecha de Envío:</span>
                                            <p className="text-gray-700 dark:text-gray-300">
                                                {order.shipped_at ? new Date(order.shipped_at).toLocaleString() : 'Pendiente de despacho'}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-xs text-gray-500 font-semibold block">Fecha de Entrega:</span>
                                            <p className="text-gray-700 dark:text-gray-300">
                                                {order.delivered_at ? new Date(order.delivered_at).toLocaleString() : 'No entregado'}
                                            </p>
                                        </div>
                                    </div>
                                </Card>

                                {/* Payment Details Card */}
                                <Card className="shadow-sm">
                                    <div className="flex items-center gap-2 border-b pb-3">
                                        <HiCreditCard className="h-5 w-5 text-emerald-600" />
                                        <h3 className="font-bold text-gray-900 dark:text-white">
                                            Información de Pago
                                        </h3>
                                    </div>
                                    <div className="space-y-3 text-sm">
                                        <div>
                                            <span className="text-xs text-gray-500 font-semibold block">Método:</span>
                                            <p className="font-bold uppercase text-gray-800 dark:text-gray-200">
                                                {order.payment_method}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-xs text-gray-500 font-semibold block">Estado de Cobro:</span>
                                            <Badge
                                                color={paymentStatusBadgeColorMap[order.payment_status] || 'gray'}
                                                className="inline-block mt-1"
                                            >
                                                {order.payment_status.toUpperCase()}
                                            </Badge>
                                        </div>
                                    </div>
                                </Card>
                            </div>
                        </div>
                    </>
                )}
            </div>

            {/* Modal: Cambiar Estado / Anulación */}
            <Modal
                show={isStatusModalOpen}
                onClose={() => setIsStatusModalOpen(false)}
                size="md"
            >
                <ModalHeader>
                    {targetStatus === 'cancelled' ? 'Anular Orden de Venta' : 'Actualizar Estado del Pedido'}
                </ModalHeader>
                <ModalBody className="space-y-4">
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        ¿Confirma el cambio de estado a{' '}
                        <Badge color={statusBadgeColorMap[targetStatus as OrderStatusType] || 'info'} className="inline-block mx-1">
                            {statusLabels[targetStatus as OrderStatusType] || targetStatus}
                        </Badge>?
                    </p>

                    {targetStatus === 'shipped' && (
                        <div>
                            <Label htmlFor="shipping_courier">Empresa de Transporte / Courier</Label>
                            <TextInput
                                id="shipping_courier"
                                value={shippingMethodInput}
                                onChange={(e) => setShippingMethodInput(e.target.value)}
                                placeholder="Ej. Chilexpress, Starken, Blue Express..."
                            />
                        </div>
                    )}

                    {targetStatus === 'cancelled' && (
                        <div>
                            <Label htmlFor="cancel_reason_detail">Motivo de la Anulación (*)</Label>
                            <Textarea
                                id="cancel_reason_detail"
                                value={cancelReasonInput}
                                onChange={(e) => setCancelReasonInput(e.target.value)}
                                placeholder="Indique la razón..."
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
                        color={targetStatus === 'cancelled' ? 'failure' : 'blue'}
                        onClick={handleExecuteStatusTransition}
                        disabled={loadingAction}
                    >
                        {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                        Confirmar
                    </Button>
                </ModalFooter>
            </Modal>
        </Dashboard>
    );
}
