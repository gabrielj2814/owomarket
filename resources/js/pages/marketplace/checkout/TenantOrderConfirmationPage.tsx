import React from 'react';
import StorefrontLayout from '@/components/layouts/StorefrontLayout';
import { useCart } from '@/contexts/CartContext';
import { StorefrontOrderConfirmationPageProps } from '@/types/models/Storefront';
import { Badge, Breadcrumb, BreadcrumbItem, Button, Card } from 'flowbite-react';
import {
    HiArrowRight,
    HiCheckCircle,
    HiHome,
    HiInformationCircle,
    HiLocationMarker,
    HiMail,
    HiPhone,
    HiPrinter,
    HiShoppingBag,
    HiTruck,
    HiUser,
} from 'react-icons/hi';
import { FaBitcoin, FaCheckCircle, FaMobileAlt, FaUniversity } from 'react-icons/fa';

function OrderConfirmationContent({
    domain,
    store_settings,
    categories = [],
    order,
    auth_user = null,
}: StorefrontOrderConfirmationPageProps) {
    const { formatPrice } = useCart();

    const handlePrint = () => {
        window.print();
    };

    return (
        <>
            <div className="max-w-4xl mx-auto space-y-8 py-4">
                {/* Breadcrumb */}
                <Breadcrumb>
                    <BreadcrumbItem href="/" icon={HiHome}>
                        Inicio
                    </BreadcrumbItem>
                    <BreadcrumbItem>Confirmación de Pedido</BreadcrumbItem>
                </Breadcrumb>

                {/* Hero Celebration Banner */}
                <div className="bg-gradient-to-r from-green-600 via-emerald-600 to-teal-700 rounded-3xl p-8 sm:p-10 text-white text-center shadow-xl space-y-4">
                    <div className="w-20 h-20 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center mx-auto text-white shadow-inner">
                        <FaCheckCircle className="w-12 h-12" />
                    </div>

                    <div className="space-y-1">
                        <span className="text-xs font-black uppercase tracking-widest bg-white/20 px-3 py-1 rounded-full">
                            ¡Compra Registrada Exitosamente!
                        </span>
                        <h1 className="text-3xl sm:text-4xl font-black tracking-tight">
                            ¡Gracias por tu compra, {order.customer.name}!
                        </h1>
                        <p className="text-sm text-green-100 max-w-md mx-auto">
                            Hemos registrado tu pedido con el número de seguimiento oficial.
                        </p>
                    </div>

                    <div className="pt-2 flex flex-wrap justify-center items-center gap-3">
                        <span className="bg-white text-gray-900 px-4 py-2 rounded-xl text-sm font-extrabold shadow">
                            Nº de Pedido: {order.order_number}
                        </span>
                        <span className="bg-green-950/40 text-green-200 border border-green-400/30 px-3.5 py-1.5 rounded-xl text-xs font-semibold">
                            Fecha: {order.created_at}
                        </span>
                    </div>
                </div>

                {/* Main Order Details (2 Columns) */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                    {/* Left Column: Items Table & Breakdown (2 Cols) */}
                    <div className="md:col-span-2 space-y-6">
                        {/* Items Table */}
                        <Card className="shadow-sm rounded-2xl">
                            <h3 className="text-base font-bold text-gray-900 dark:text-white pb-3 border-b dark:border-gray-800">
                                Artículos Comprados ({order.items.length})
                            </h3>

                            <div className="divide-y dark:divide-gray-800">
                                {order.items.map((item) => (
                                    <div
                                        key={item.id}
                                        className="py-3 flex items-center justify-between gap-4 text-xs"
                                    >
                                        <div className="space-y-0.5 min-w-0">
                                            <p className="font-bold text-gray-900 dark:text-white truncate text-sm">
                                                {item.product_name}
                                            </p>
                                            {item.attributes && (
                                                <div className="flex flex-wrap gap-1">
                                                    {Object.entries(item.attributes).map(([k, v]) => (
                                                        <span
                                                            key={k}
                                                            className="text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-md"
                                                        >
                                                            {k}: {v}
                                                        </span>
                                                    ))}
                                                </div>
                                            )}
                                            <p className="text-[11px] text-gray-400">
                                                SKU: {item.sku} • Cant: {item.quantity} × {formatPrice(item.price)}
                                            </p>
                                        </div>

                                        <span className="font-extrabold text-sm text-gray-900 dark:text-white flex-shrink-0">
                                            {formatPrice(item.total)}
                                        </span>
                                    </div>
                                ))}
                            </div>

                            {/* Financial Breakdown */}
                            <div className="space-y-2 text-xs text-gray-600 dark:text-gray-300 pt-4 border-t dark:border-gray-800">
                                <div className="flex justify-between">
                                    <span>Subtotal:</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">
                                        {formatPrice(order.subtotal)}
                                    </span>
                                </div>

                                {order.discount_amount > 0 && (
                                    <div className="flex justify-between text-green-600 dark:text-green-400 font-semibold">
                                        <span>Descuento aplicado:</span>
                                        <span>-{formatPrice(order.discount_amount)}</span>
                                    </div>
                                )}

                                <div className="flex justify-between">
                                    <span>Costo de Despacho:</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">
                                        {order.shipping_amount === 0 ? 'Gratis' : formatPrice(order.shipping_amount)}
                                    </span>
                                </div>

                                <div className="flex justify-between items-baseline pt-3 border-t dark:border-gray-800 text-base">
                                    <span className="font-bold text-gray-900 dark:text-white">Monto Total:</span>
                                    <span className="text-2xl font-black text-gray-900 dark:text-white">
                                        {formatPrice(order.total)}
                                    </span>
                                </div>
                            </div>
                        </Card>

                        {/* Payment Confirmation & Details Box */}
                        {order.payment_method === 'pago_movil' && (
                            <Card className="shadow-sm rounded-2xl bg-gradient-to-br from-blue-50/70 to-indigo-50/50 dark:from-gray-900 dark:to-blue-950/30 border-blue-200 dark:border-blue-800/60">
                                <div className="flex items-center justify-between border-b border-blue-100 dark:border-gray-800 pb-2.5">
                                    <div className="flex items-center gap-2 text-blue-900 dark:text-blue-300 font-bold text-sm">
                                        <FaMobileAlt className="w-5 h-5 text-blue-600" />
                                        <span>Comprobante de Pago Móvil Registrado</span>
                                    </div>
                                    <Badge color="warning" size="xs">Pendiente de Verificación</Badge>
                                </div>
                                <div className="text-xs text-gray-700 dark:text-gray-300 space-y-2 leading-relaxed">
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 p-3 bg-white dark:bg-gray-800/80 rounded-xl border text-[11px] font-mono">
                                        <div>
                                            <span className="text-gray-400 block text-[10px]">BANCO EMISOR</span>
                                            <strong className="text-gray-900 dark:text-white">{(order as any).payment_details?.bank_origin || 'No especificado'}</strong>
                                        </div>
                                        <div>
                                            <span className="text-gray-400 block text-[10px]">TELÉFONO</span>
                                            <strong className="text-gray-900 dark:text-white">{(order as any).payment_details?.phone_origin || order.customer.phone || 'No especificado'}</strong>
                                        </div>
                                        <div>
                                            <span className="text-gray-400 block text-[10px]">Nº REFERENCIA</span>
                                            <strong className="text-blue-600 dark:text-blue-400 text-xs">{(order as any).payment_details?.reference_number || 'N/A'}</strong>
                                        </div>
                                    </div>
                                    <p className="text-[11px] text-gray-500">
                                        Hemos recibido los datos de tu Pago Móvil. Nuestro equipo conciliará la referencia con el extracto bancario para despachar tu compra.
                                    </p>
                                </div>
                            </Card>
                        )}

                        {order.payment_method === 'binance_pay' && (
                            <Card className="shadow-sm rounded-2xl bg-gradient-to-br from-amber-50/70 to-yellow-50/40 dark:from-gray-900 dark:to-amber-950/30 border-amber-200 dark:border-amber-800/60">
                                <div className="flex items-center justify-between border-b border-amber-100 dark:border-gray-800 pb-2.5">
                                    <div className="flex items-center gap-2 text-amber-900 dark:text-amber-300 font-bold text-sm">
                                        <FaBitcoin className="w-5 h-5 text-amber-500" />
                                        <span>Comprobante de Binance Pay (USDT)</span>
                                    </div>
                                    <Badge color="warning" size="xs">Validando en Blockchain / Pay</Badge>
                                </div>
                                <div className="text-xs text-gray-700 dark:text-gray-300 space-y-2 leading-relaxed">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-white dark:bg-gray-800/80 rounded-xl border text-[11px] font-mono">
                                        <div>
                                            <span className="text-gray-400 block text-[10px]">BINANCE BUYER ID</span>
                                            <strong className="text-gray-900 dark:text-white">{(order as any).payment_details?.binance_id || 'Comprador Binance'}</strong>
                                        </div>
                                        <div>
                                            <span className="text-gray-400 block text-[10px]">TX HASH / ORDER ID</span>
                                            <strong className="text-amber-600 dark:text-amber-400 text-xs break-all">{(order as any).payment_details?.transaction_hash || 'N/A'}</strong>
                                        </div>
                                    </div>
                                    <p className="text-[11px] text-gray-500">
                                        Tu transacción en USDT ha sido registrada. Una vez confirmada en Binance Pay, la orden cambiará automáticamente a estado Pagada.
                                    </p>
                                </div>
                            </Card>
                        )}

                        {order.payment_method === 'bank_transfer' && (
                            <Card className="shadow-sm rounded-2xl bg-blue-50/60 dark:bg-blue-950/30 border-blue-200 dark:border-blue-900">
                                <div className="flex items-center gap-2 text-blue-900 dark:text-blue-300 font-bold text-sm">
                                    <FaUniversity className="w-5 h-5 text-blue-600" />
                                    <span>Instrucciones de Transferencia Bancaria</span>
                                </div>
                                <div className="text-xs text-gray-700 dark:text-gray-300 space-y-2 leading-relaxed">
                                    <p>
                                        Por favor realiza la transferencia bancaria por un monto de{' '}
                                        <strong>{formatPrice(order.total)}</strong> indicando tu número de orden{' '}
                                        <strong>#{order.order_number}</strong> en el comentario o asunto.
                                    </p>
                                    <div className="p-3 bg-white dark:bg-gray-900 rounded-xl border font-mono text-[11px] space-y-1">
                                        <p><strong>Comercio:</strong> {store_settings?.store_name || 'Tienda'}</p>
                                        <p><strong>Email de comprobante:</strong> {store_settings?.store_email || 'pagos@tienda.com'}</p>
                                        <p><strong>Nº de Orden:</strong> {order.order_number}</p>
                                    </div>
                                    <p className="text-[11px] text-gray-500">
                                        Una vez recibido y verificado el comprobante, despacharemos tu pedido a la brevedad.
                                    </p>
                                </div>
                            </Card>
                        )}
                    </div>

                    {/* Right Column: Customer & Shipping Summary (1 Col) */}
                    <div className="space-y-6">
                        <Card className="shadow-sm rounded-2xl space-y-4">
                            <h3 className="text-sm font-bold text-gray-900 dark:text-white pb-2 border-b dark:border-gray-800">
                                Datos del Comprador
                            </h3>

                            <div className="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                                <div className="flex items-center gap-2">
                                    <HiUser className="w-4 h-4 text-blue-500 flex-shrink-0" />
                                    <span className="font-semibold text-gray-900 dark:text-white">
                                        {order.customer.name}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <HiMail className="w-4 h-4 text-blue-500 flex-shrink-0" />
                                    <span>{order.customer.email}</span>
                                </div>
                                {order.customer.phone && (
                                    <div className="flex items-center gap-2">
                                        <HiPhone className="w-4 h-4 text-blue-500 flex-shrink-0" />
                                        <span>{order.customer.phone}</span>
                                    </div>
                                )}
                            </div>

                            <h3 className="text-sm font-bold text-gray-900 dark:text-white pt-2 pb-2 border-t dark:border-gray-800">
                                Dirección de Despacho
                            </h3>

                            <div className="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                                {order.shipping_address ? (
                                    <>
                                        <div className="flex items-start gap-2">
                                            <HiLocationMarker className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                                            <span>
                                                {order.shipping_address.address}, {order.shipping_address.city}
                                                {order.shipping_address.state && `, ${order.shipping_address.state}`}
                                            </span>
                                        </div>
                                        {order.shipping_address.notes && (
                                            <p className="text-[11px] text-gray-400 pl-6 italic">
                                                "{order.shipping_address.notes}"
                                            </p>
                                        )}
                                    </>
                                ) : (
                                    <p className="text-gray-400">Coordinar con la tienda</p>
                                )}
                            </div>

                            {/* Action Buttons */}
                            <div className="pt-4 border-t dark:border-gray-800 space-y-2">
                                <Button
                                    color="gray"
                                    size="sm"
                                    className="w-full"
                                    onClick={handlePrint}
                                >
                                    <HiPrinter className="mr-2 h-4 w-4" />
                                    Imprimir Comprobante
                                </Button>

                                <Button
                                    color="blue"
                                    size="md"
                                    className="w-full font-bold"
                                    onClick={() => (window.location.href = '/')}
                                >
                                    <span className="flex items-center justify-center gap-2">
                                        Volver a la Tienda
                                        <HiArrowRight className="w-4 h-4" />
                                    </span>
                                </Button>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

export default function TenantOrderConfirmationPage(props: StorefrontOrderConfirmationPageProps) {
    return (
        <StorefrontLayout
            domain={props.domain}
            title={`Orden Confirmada #${props.order.order_number}`}
            storeSettings={props.store_settings}
            categories={props.categories}
            authUser={props.auth_user}
        >
            <OrderConfirmationContent {...props} />
        </StorefrontLayout>
    );
}
