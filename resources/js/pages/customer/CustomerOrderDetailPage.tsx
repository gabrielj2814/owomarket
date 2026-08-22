import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CustomerPortalServices, {
    CustomerOrderData,
    OrderTrackingData,
} from '@/Services/CustomerPortalServices';
import OrderTrackingTimeline from '@/components/ui/storefront/OrderTrackingTimeline';
import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';
import {
    HiOutlineArrowLeft,
    HiOutlineDocumentArrowDown,
    HiOutlineBuildingStorefront,
    HiOutlineMapPin,
    HiOutlineCreditCard,
    HiOutlineShieldCheck,
} from 'react-icons/hi2';

export const CustomerOrderDetailPage: React.FC = () => {
    const { props } = usePage<{ orderId?: string }>();
    const { customer } = useCustomerAuth();

    // Extract orderId from URL or props
    const urlParts = window.location.pathname.split('/');
    const orderId = props.orderId || urlParts[urlParts.length - 1];

    const [order, setOrder] = useState<CustomerOrderData | null>(null);
    const [tracking, setTracking] = useState<OrderTrackingData | null>(null);
    const [loading, setLoading] = useState(true);
    // Hallazgo N35: un error de red era indistinguible de «no tienes nada».
    const [loadError, setLoadError] = useState(false);

    useEffect(() => {
        if (!customer?.id || !orderId) return;

        Promise.all([
            CustomerPortalServices.getOrderDetail(customer.id, orderId),
            CustomerPortalServices.getOrderTracking(customer.id, orderId),
        ])
            .then(([orderRes, trackingRes]) => {
                if (orderRes?.data) setOrder(orderRes.data);
                if (trackingRes?.data) setTracking(trackingRes.data);
            })
            .catch(() => setLoadError(true))
            .finally(() => setLoading(false));
    }, [customer?.id, orderId]);

    if (loading) {
        return (
            <CustomerAccountLayout title="Detalle de la Orden">
            {loadError && <PortalLoadError />}

                <div className="text-center py-20 text-gray-400">
                    <p className="text-sm font-medium">Cargando información del pedido y tracking...</p>
                </div>
            </CustomerAccountLayout>
        );
    }

    if (!order) {
        return (
            <CustomerAccountLayout title="Pedido no encontrado">
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center border border-gray-200 dark:border-gray-800">
                    <h3 className="text-base font-bold text-gray-900 dark:text-white mb-2">
                        No se pudo encontrar el pedido especificado
                    </h3>
                    <Link
                        href="/account/orders"
                        className="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold"
                    >
                        <HiOutlineArrowLeft className="w-4 h-4" /> Volver a mis pedidos
                    </Link>
                </div>
            </CustomerAccountLayout>
        );
    }

    return (
        <CustomerAccountLayout
            title={`Pedido ${order.order_number}`}
            description={`Realizado el ${order.created_at ? order.created_at.substring(0, 10) : ''} • Estado: ${order.status.toUpperCase()}`}
        >
            <Head title={`Orden ${order.order_number} - OwOMarket`} />

            {/* Back to Orders & Invoice PDF Action */}
            <div className="flex items-center justify-between mb-6">
                <Link
                    href="/account/orders"
                    className="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-blue-600 transition"
                >
                    <HiOutlineArrowLeft className="w-4 h-4" />
                    Volver a mis pedidos
                </Link>

                <a
                    href={CustomerPortalServices.getInvoicePdfUrl(customer?.id || '', order.id)}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition"
                >
                    <HiOutlineDocumentArrowDown className="w-4 h-4" />
                    Descargar Factura PDF Oficial
                </a>
            </div>

            {/* Tracking Timeline */}
            {tracking && (
                <div className="mb-8">
                    <OrderTrackingTimeline
                        currentStep={tracking.current_step}
                        courier={tracking.courier}
                        trackingNumber={tracking.tracking_number}
                        trackingUrl={tracking.tracking_url}
                        timeline={tracking.timeline}
                    />
                </div>
            )}

            {/* Order Items & Summary Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Items List */}
                <div className="lg:col-span-2 space-y-4">
                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80">
                        <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                            <HiOutlineBuildingStorefront className="w-4 h-4 text-blue-600" />
                            Productos Comprados ({order.items?.length || 0})
                        </h3>

                        <div className="divide-y divide-gray-100 dark:divide-gray-800">
                            {order.items?.map(item => (
                                <div key={item.id} className="py-4 flex items-center justify-between gap-4">
                                    <div>
                                        <h4 className="text-xs font-bold text-gray-900 dark:text-white">
                                            {item.product_name}
                                        </h4>
                                        <span className="text-[11px] text-gray-400">
                                            Tienda: {item.tenant_id} • Cantidad: {item.quantity} • Precio Unitario: ${item.price.toFixed(2)}
                                        </span>
                                    </div>
                                    <div className="text-right">
                                        <CurrencyPriceDisplay priceUsd={item.total} size="sm" showVes={true} showBcvLabel={false} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Shipping & Payment Summary */}
                <div className="space-y-4">
                    {/* Shipping info */}
                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80">
                        <h3 className="text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider mb-3 flex items-center gap-2">
                            <HiOutlineMapPin className="w-4 h-4 text-blue-600" />
                            Dirección de Envío
                        </h3>
                        <p className="text-xs font-bold text-gray-800 dark:text-gray-200">
                            {order.customer_name}
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {order.shipping_address?.address || 'Dirección no especificada'}
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {order.shipping_address?.city} {order.shipping_address?.state ? `, ${order.shipping_address?.state}` : ''}
                        </p>
                        {order.customer_phone && (
                            <p className="text-xs text-gray-400 mt-2">
                                Tel: {order.customer_phone}
                            </p>
                        )}
                    </div>

                    {/* Payment Info */}
                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80">
                        <h3 className="text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider mb-3 flex items-center gap-2">
                            <HiOutlineCreditCard className="w-4 h-4 text-blue-600" />
                            Detalle del Pago
                        </h3>
                        <div className="space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                            <div className="flex justify-between">
                                <span>Método:</span>
                                <strong className="text-gray-900 dark:text-white font-bold capitalize">
                                    {order.payment_method === 'pago_movil' ? 'Pago Móvil' : 'Binance Pay'}
                                </strong>
                            </div>
                            {order.payment_details?.reference_number && (
                                <div className="flex justify-between">
                                    <span>Referencia:</span>
                                    <strong className="text-gray-900 dark:text-white font-mono">
                                        {order.payment_details.reference_number}
                                    </strong>
                                </div>
                            )}
                            <div className="flex justify-between">
                                <span>Estado:</span>
                                <span className="text-green-600 dark:text-green-400 font-black uppercase text-[10px]">
                                    {order.payment_status}
                                </span>
                            </div>
                        </div>

                        <hr className="my-3 border-gray-100 dark:border-gray-800" />

                        {/* Totals */}
                        <div className="space-y-1 text-xs">
                            <div className="flex justify-between text-gray-500">
                                <span>Subtotal:</span>
                                <span>${order.subtotal.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between text-gray-500">
                                <span>Descuentos:</span>
                                <span>-${order.discount_amount.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between text-gray-500">
                                <span>Envío:</span>
                                <span>${order.shipping_amount.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between font-black text-gray-900 dark:text-white text-sm pt-2 border-t border-gray-100 dark:border-gray-800">
                                <span>Total Final:</span>
                                <CurrencyPriceDisplay priceUsd={order.total} size="md" showVes={true} showBcvLabel={true} />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </CustomerAccountLayout>
    );
};

export default CustomerOrderDetailPage;
