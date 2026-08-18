import React, { useEffect, useState } from 'react';
import Dashboard from '@/components/layouts/Dashboard';
import BillingServices from '@/Services/BillingServices';
import OrderServices from '@/Services/OrderServices';
import ShipmentServices from '@/Services/ShipmentServices';
import { FormDirectInvoice } from '@/types/FormDirectInvoice';
import { FormCreateShipment, FormUpdateTracking } from '@/types/FormShipment';
import { Order, OrderStatusType, PaymentStatusType } from '@/types/models/Order';
import { Shipment, ShipmentStatusType } from '@/types/models/Shipment';
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
    HiPlus,
    HiPrinter,
    HiRefresh,
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

const shipmentStatusBadgeColorMap: Record<ShipmentStatusType, string> = {
    pending: 'warning',
    in_transit: 'purple',
    delivered: 'success',
};

const shipmentStatusLabels: Record<ShipmentStatusType, string> = {
    pending: 'Preparación / Pendiente',
    in_transit: 'En Tránsito / Despachado',
    delivered: 'Entregado al Destinatario',
};

export default function ShowOrderDetailPage({
    title,
    user_id,
    order_id,
    host,
    user_name,
}: ShowOrderDetailPageProps) {
    const [order, setOrder] = useState<Order | null>(null);
    const [shipments, setShipments] = useState<Shipment[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [loadingShipments, setLoadingShipments] = useState<boolean>(false);
    const [loadingAction, setLoadingAction] = useState<boolean>(false);
    const [toastMessage, setToastMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    // Modal state for order status change
    const [isStatusModalOpen, setIsStatusModalOpen] = useState<boolean>(false);
    const [targetStatus, setTargetStatus] = useState<string>('confirmed');
    const [shippingMethodInput, setShippingMethodInput] = useState<string>('');
    const [cancelReasonInput, setCancelReasonInput] = useState<string>('');

    // State for invoice generation
    const [invoiceCreatedSuccess, setInvoiceCreatedSuccess] = useState<string | null>(null);

    // Modal state for Shipments
    const [isCreateShipmentModalOpen, setIsCreateShipmentModalOpen] = useState<boolean>(false);
    const [formShipment, setFormShipment] = useState<FormCreateShipment>({
        order_id: order_id,
        carrier: 'Chilexpress',
        service: 'Express 24h',
        cost: 0,
        tracking_number: '',
        notes: '',
        estimated_delivery: '',
    });

    const [isUpdateTrackingModalOpen, setIsUpdateTrackingModalOpen] = useState<boolean>(false);
    const [selectedShipment, setSelectedShipment] = useState<Shipment | null>(null);
    const [formTracking, setFormTracking] = useState<FormUpdateTracking>({
        tracking_number: '',
        carrier: '',
        service: '',
        cost: 0,
        estimated_delivery: '',
        notes: '',
    });

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
                setFormShipment((prev) => ({
                    ...prev,
                    cost: Number(response.data.data?.shipping_amount || 0),
                    carrier: response.data.data?.shipping_method || 'Chilexpress',
                }));
            } else {
                showToast('error', response.data?.message || 'Error al consultar la orden.');
            }
        } catch (e) {
            showToast('error', 'Error al comunicarse con el servidor.');
        } finally {
            setLoading(false);
        }
    };

    const fetchShipments = async () => {
        setLoadingShipments(true);
        try {
            const response = await ShipmentServices.consultByOrderId(order_id);
            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                setShipments(response.data.data || []);
            }
        } catch (e) {
            // Silently fail or minimal feedback
        } finally {
            setLoadingShipments(false);
        }
    };

    useEffect(() => {
        fetchOrder();
        fetchShipments();
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
                fetchShipments();
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

    // Shipment Creation Handler
    const handleCreateShipment = async () => {
        if (!order) return;
        setLoadingAction(true);

        try {
            const payload: FormCreateShipment = {
                ...formShipment,
                order_id: order.id,
                cost: Number(formShipment.cost || 0),
            };

            const response = await ShipmentServices.create(payload);
            if (response.data && (response.data.code === 201 || response.data.status === 'success')) {
                showToast('success', '¡Guía de despacho registrada exitosamente!');
                setIsCreateShipmentModalOpen(false);
                fetchOrder();
                fetchShipments();
            } else {
                showToast('error', response.data?.message || 'Error al registrar el envío.');
            }
        } catch (e) {
            showToast('error', 'Error al comunicarse con el servidor.');
        } finally {
            setLoadingAction(false);
        }
    };

    // Open Update Tracking Modal
    const handleOpenUpdateTracking = (shipment: Shipment) => {
        setSelectedShipment(shipment);
        setFormTracking({
            tracking_number: shipment.tracking_number || '',
            carrier: shipment.carrier,
            service: shipment.service,
            cost: Number(shipment.cost),
            estimated_delivery: shipment.estimated_delivery ? shipment.estimated_delivery.split('T')[0] : '',
            notes: shipment.notes || '',
        });
        setIsUpdateTrackingModalOpen(true);
    };

    // Execute Tracking Update
    const handleExecuteUpdateTracking = async () => {
        if (!selectedShipment) return;
        setLoadingAction(true);

        try {
            const response = await ShipmentServices.updateTracking(selectedShipment.id, formTracking);
            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                showToast('success', 'Seguimiento de despacho actualizado correctamente.');
                setIsUpdateTrackingModalOpen(false);
                fetchOrder();
                fetchShipments();
            } else {
                showToast('error', response.data?.message || 'Error al actualizar el tracking.');
            }
        } catch (e) {
            showToast('error', 'Error de conexión.');
        } finally {
            setLoadingAction(false);
        }
    };

    // Execute Mark Shipment as Delivered
    const handleMarkShipmentDelivered = async (shipmentId: string) => {
        setLoadingAction(true);
        try {
            const response = await ShipmentServices.markAsDelivered(shipmentId);
            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                showToast('success', 'El envío ha sido marcado como ENTREGADO.');
                fetchOrder();
                fetchShipments();
            } else {
                showToast('error', response.data?.message || 'Error al actualizar el envío.');
            }
        } catch (e) {
            showToast('error', 'Error de conexión.');
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
                                        onClick={() => setIsCreateShipmentModalOpen(true)}
                                        disabled={loadingAction || order.status === 'cancelled'}
                                    >
                                        <HiTruck className="mr-1 h-4 w-4" />
                                        Nueva Guía Despacho
                                    </Button>

                                    <Button
                                        color="purple"
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
                            {/* Left Column (Items, Shipments & Financial Summary) - 2 Cols */}
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

                                {/* Physical Shipments Section */}
                                <Card className="shadow-sm">
                                    <div className="flex justify-between items-center border-b pb-3">
                                        <div className="flex items-center gap-2">
                                            <HiTruck className="h-6 w-6 text-indigo-600" />
                                            <div>
                                                <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                                    Guías de Despacho y Seguimiento ({shipments.length})
                                                </h3>
                                                <p className="text-xs text-gray-500">
                                                    Control logístico de envíos físicos, couriers y números de tracking.
                                                </p>
                                            </div>
                                        </div>
                                        <Button
                                            color="blue"
                                            size="xs"
                                            onClick={() => setIsCreateShipmentModalOpen(true)}
                                            disabled={loadingAction || order.status === 'cancelled'}
                                        >
                                            <HiPlus className="mr-1 h-3.5 w-3.5" />
                                            Nueva Guía
                                        </Button>
                                    </div>

                                    {loadingShipments ? (
                                        <div className="py-8 text-center">
                                            <Spinner size="md" />
                                            <p className="text-xs text-gray-500 mt-2">Cargando envíos...</p>
                                        </div>
                                    ) : shipments.length === 0 ? (
                                        <div className="text-center py-8 bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
                                            <HiTruck className="mx-auto h-10 w-10 text-gray-400" />
                                            <p className="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-2">
                                                Aún no se han generado guías de despacho para este pedido.
                                            </p>
                                            <p className="text-xs text-gray-500 mt-1">
                                                Emite la primera guía para registrar la empresa de transporte y número de tracking.
                                            </p>
                                            <Button
                                                color="blue"
                                                size="sm"
                                                className="mt-4 mx-auto"
                                                onClick={() => setIsCreateShipmentModalOpen(true)}
                                            >
                                                Crear Guía de Despacho
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {shipments.map((ship) => (
                                                <div
                                                    key={ship.id}
                                                    className="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col md:flex-row justify-between gap-4"
                                                >
                                                    <div className="space-y-2">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-bold text-gray-900 dark:text-white text-base">
                                                                {ship.carrier}
                                                            </span>
                                                            <span className="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded font-medium">
                                                                {ship.service}
                                                            </span>
                                                            <Badge
                                                                color={shipmentStatusBadgeColorMap[ship.status] || 'gray'}
                                                                size="sm"
                                                            >
                                                                {shipmentStatusLabels[ship.status] || ship.status}
                                                            </Badge>
                                                        </div>

                                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                                            <div>
                                                                <span className="font-semibold text-gray-500">N° Tracking: </span>
                                                                {ship.tracking_number ? (
                                                                    <span className="font-mono font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded">
                                                                        {ship.tracking_number}
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-amber-600 font-semibold italic">
                                                                        Pendiente de asignar
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <div>
                                                                <span className="font-semibold text-gray-500">Costo Despacho: </span>
                                                                <span className="font-bold text-gray-900 dark:text-white">
                                                                    ${Number(ship.cost).toFixed(2)} USD
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <span className="font-semibold text-gray-500">Fecha Despacho: </span>
                                                                <span>{ship.shipped_at ? new Date(ship.shipped_at).toLocaleString() : 'No despachado'}</span>
                                                            </div>
                                                            <div>
                                                                <span className="font-semibold text-gray-500">Entrega Estimada: </span>
                                                                <span>{ship.estimated_delivery ? new Date(ship.estimated_delivery).toLocaleDateString() : 'No especificada'}</span>
                                                            </div>
                                                            {ship.delivered_at && (
                                                                <div className="sm:col-span-2 text-emerald-600 font-semibold flex items-center gap-1">
                                                                    <HiCheckCircle className="h-4 w-4" />
                                                                    <span>Entregado el: {new Date(ship.delivered_at).toLocaleString()}</span>
                                                                </div>
                                                            )}
                                                        </div>

                                                        {ship.notes && (
                                                            <p className="text-xs text-gray-500 italic mt-1 bg-gray-50 dark:bg-gray-700/50 p-2 rounded">
                                                                Nota: {ship.notes}
                                                            </p>
                                                        )}
                                                    </div>

                                                    {/* Actions per shipment */}
                                                    <div className="flex md:flex-col justify-end gap-2 shrink-0">
                                                        <Button
                                                            color="gray"
                                                            size="xs"
                                                            onClick={() => handleOpenUpdateTracking(ship)}
                                                            disabled={loadingAction || ship.status === 'delivered'}
                                                        >
                                                            <HiRefresh className="mr-1 h-3.5 w-3.5" />
                                                            {ship.tracking_number ? 'Modificar Tracking' : 'Asignar Tracking'}
                                                        </Button>

                                                        {ship.status !== 'delivered' && (
                                                            <Button
                                                                color="success"
                                                                size="xs"
                                                                onClick={() => handleMarkShipmentDelivered(ship.id)}
                                                                disabled={loadingAction}
                                                            >
                                                                <HiCheckCircle className="mr-1 h-3.5 w-3.5" />
                                                                Marcar Entregado
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
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

            {/* Modal: Cambiar Estado / Anulación del Pedido */}
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

            {/* Modal: Crear Nueva Guía de Despacho */}
            <Modal
                show={isCreateShipmentModalOpen}
                onClose={() => setIsCreateShipmentModalOpen(false)}
                size="lg"
            >
                <ModalHeader>
                    <div className="flex items-center gap-2">
                        <HiTruck className="h-6 w-6 text-blue-600" />
                        <span>Generar Guía de Despacho y Envío</span>
                    </div>
                </ModalHeader>
                <ModalBody className="space-y-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="ship_carrier">Empresa de Transporte / Courier (*)</Label>
                            <TextInput
                                id="ship_carrier"
                                value={formShipment.carrier}
                                onChange={(e) => setFormShipment({ ...formShipment, carrier: e.target.value })}
                                placeholder="Ej. Chilexpress, Starken, DHL, FedEx..."
                                required
                            />
                        </div>
                        <div>
                            <Label htmlFor="ship_service">Tipo de Servicio (*)</Label>
                            <TextInput
                                id="ship_service"
                                value={formShipment.service}
                                onChange={(e) => setFormShipment({ ...formShipment, service: e.target.value })}
                                placeholder="Ej. Express 24h, Estándar, Same Day..."
                                required
                            />
                        </div>
                        <div>
                            <Label htmlFor="ship_cost">Costo Real de Despacho ($ USD)</Label>
                            <TextInput
                                id="ship_cost"
                                type="number"
                                step="0.01"
                                min="0"
                                value={formShipment.cost}
                                onChange={(e) => setFormShipment({ ...formShipment, cost: parseFloat(e.target.value) || 0 })}
                                placeholder="0.00"
                            />
                        </div>
                        <div>
                            <Label htmlFor="ship_tracking">Número de Tracking / Seguimiento</Label>
                            <TextInput
                                id="ship_tracking"
                                value={formShipment.tracking_number || ''}
                                onChange={(e) => setFormShipment({ ...formShipment, tracking_number: e.target.value })}
                                placeholder="Ej. CHI-99887766 (opcional)"
                            />
                        </div>
                        <div className="sm:col-span-2">
                            <Label htmlFor="ship_est_delivery">Fecha Estimada de Entrega</Label>
                            <TextInput
                                id="ship_est_delivery"
                                type="date"
                                value={formShipment.estimated_delivery || ''}
                                onChange={(e) => setFormShipment({ ...formShipment, estimated_delivery: e.target.value })}
                            />
                        </div>
                        <div className="sm:col-span-2">
                            <Label htmlFor="ship_notes">Instrucciones / Notas de Despacho</Label>
                            <Textarea
                                id="ship_notes"
                                value={formShipment.notes || ''}
                                onChange={(e) => setFormShipment({ ...formShipment, notes: e.target.value })}
                                placeholder="Notas internas para el equipo de despacho o conductor..."
                                rows={3}
                            />
                        </div>
                    </div>
                </ModalBody>
                <ModalFooter className="flex justify-end gap-2">
                    <Button color="gray" onClick={() => setIsCreateShipmentModalOpen(false)}>
                        Cancelar
                    </Button>
                    <Button
                        color="blue"
                        onClick={handleCreateShipment}
                        disabled={loadingAction || !formShipment.carrier || !formShipment.service}
                    >
                        {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                        Registrar Guía de Despacho
                    </Button>
                </ModalFooter>
            </Modal>

            {/* Modal: Actualizar Tracking / Courier */}
            <Modal
                show={isUpdateTrackingModalOpen}
                onClose={() => setIsUpdateTrackingModalOpen(false)}
                size="md"
            >
                <ModalHeader>
                    Actualizar Seguimiento de Despacho
                </ModalHeader>
                <ModalBody className="space-y-4">
                    <div>
                        <Label htmlFor="upd_tracking">Número de Tracking (*)</Label>
                        <TextInput
                            id="upd_tracking"
                            value={formTracking.tracking_number}
                            onChange={(e) => setFormTracking({ ...formTracking, tracking_number: e.target.value })}
                            placeholder="Ej. CHI-99887766"
                            required
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="upd_carrier">Courier</Label>
                            <TextInput
                                id="upd_carrier"
                                value={formTracking.carrier || ''}
                                onChange={(e) => setFormTracking({ ...formTracking, carrier: e.target.value })}
                            />
                        </div>
                        <div>
                            <Label htmlFor="upd_service">Servicio</Label>
                            <TextInput
                                id="upd_service"
                                value={formTracking.service || ''}
                                onChange={(e) => setFormTracking({ ...formTracking, service: e.target.value })}
                            />
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="upd_est_delivery">Fecha Estimada de Entrega</Label>
                        <TextInput
                            id="upd_est_delivery"
                            type="date"
                            value={formTracking.estimated_delivery || ''}
                            onChange={(e) => setFormTracking({ ...formTracking, estimated_delivery: e.target.value })}
                        />
                    </div>
                    <div>
                        <Label htmlFor="upd_notes">Notas de Despacho</Label>
                        <Textarea
                            id="upd_notes"
                            value={formTracking.notes || ''}
                            onChange={(e) => setFormTracking({ ...formTracking, notes: e.target.value })}
                            rows={2}
                        />
                    </div>
                </ModalBody>
                <ModalFooter className="flex justify-end gap-2">
                    <Button color="gray" onClick={() => setIsUpdateTrackingModalOpen(false)}>
                        Cancelar
                    </Button>
                    <Button
                        color="blue"
                        onClick={handleExecuteUpdateTracking}
                        disabled={loadingAction || !formTracking.tracking_number}
                    >
                        {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                        Guardar Cambios
                    </Button>
                </ModalFooter>
            </Modal>
        </Dashboard>
    );
}
