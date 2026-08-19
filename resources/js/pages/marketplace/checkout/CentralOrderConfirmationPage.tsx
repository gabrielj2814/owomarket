import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CentralLayout from '@/components/layouts/CentralLayout';
import CentralMarketplaceServices, {
    CentralOrderConfirmationResponse,
} from '@/Services/CentralMarketplaceServices';
import {
    HiOutlineCheckCircle,
    HiOutlineBuildingStorefront,
    HiOutlineDocumentText,
    HiOutlinePrinter,
    HiOutlineShoppingBag,
    HiOutlineArrowPath,
} from 'react-icons/hi2';

interface CentralOrderConfirmationPageProps {
    domain?: string;
    order_id: string;
}

const CentralOrderConfirmationPageContent: React.FC<CentralOrderConfirmationPageProps> = ({
    domain,
    order_id,
}) => {
    const [order, setOrder] = useState<CentralOrderConfirmationResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        CentralMarketplaceServices.getOrderConfirmation(order_id).then(res => {
            if ((res.code === 200 || res.status === 'success') && res.data) {
                setOrder(res.data);
            } else {
                setError(res.message || 'No se pudo cargar la información de la orden');
            }
            setLoading(false);
        });
    }, [order_id]);

    const handlePrint = () => {
        window.print();
    };

    if (loading) {
        return (
            <div className="py-24 text-center space-y-4">
                <HiOutlineArrowPath className="w-10 h-10 text-blue-600 animate-spin mx-auto" />
                <p className="text-sm font-semibold text-gray-500">Cargando confirmación de la orden unificada...</p>
            </div>
        );
    }

    if (error || !order) {
        return (
            <div className="py-20 text-center space-y-4">
                <div className="p-4 rounded-2xl bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 max-w-md mx-auto text-sm font-bold">
                    {error || 'Orden no encontrada'}
                </div>
                <Link
                    href="/"
                    className="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl text-xs"
                >
                    Volver a la Portada
                </Link>
            </div>
        );
    }

    return (
        <>
            <Head title={`Factura ${order.order_number} - OwOMarket Central`} />

            <div className="max-w-4xl mx-auto space-y-8">
                {/* Success Banner */}
                <div className="rounded-3xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white p-8 shadow-xl text-center space-y-3">
                    <div className="w-16 h-16 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center mx-auto">
                        <HiOutlineCheckCircle className="w-10 h-10 text-white" />
                    </div>
                    <h1 className="text-2xl sm:text-3xl font-black">
                        ¡Gracias por tu compra en OwOMarket!
                    </h1>
                    <p className="text-xs sm:text-sm text-emerald-100 max-w-lg mx-auto">
                        Tu orden unificada <strong>{order.order_number}</strong> ha sido recibida exitosamente. Hemos notificado a cada una de las tiendas vendedoras para preparar tus paquetes.
                    </p>
                </div>

                {/* Printable Invoice Container */}
                <div className="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 sm:p-10 space-y-8 shadow-sm print:border-none print:shadow-none">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-6">
                        <div className="flex items-center gap-3">
                            <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-xl flex items-center justify-center">
                                OwO
                            </div>
                            <div>
                                <h2 className="text-lg font-black text-gray-900 dark:text-white">
                                    OwOMarket Central
                                </h2>
                                <p className="text-xs text-gray-500">Factura Unificada Consolidada</p>
                            </div>
                        </div>

                        <div className="text-left sm:text-right space-y-1">
                            <span className="text-xs font-bold px-2.5 py-1 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 uppercase">
                                Orden: {order.order_number}
                            </span>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                Fecha: {new Date(order.created_at).toLocaleString()}
                            </p>
                        </div>
                    </div>

                    {/* Customer & Payment Info */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/50 text-xs">
                        <div className="space-y-1">
                            <span className="font-bold text-gray-400 uppercase tracking-wider block text-[10px]">
                                Datos del Cliente
                            </span>
                            <p className="font-bold text-gray-900 dark:text-white">{order.customer?.name}</p>
                            <p className="text-gray-500">{order.customer?.email}</p>
                            {order.customer?.phone && <p className="text-gray-500">{order.customer?.phone}</p>}
                        </div>

                        <div className="space-y-1">
                            <span className="font-bold text-gray-400 uppercase tracking-wider block text-[10px]">
                                Dirección de Envío
                            </span>
                            <p className="text-gray-700 dark:text-gray-300">
                                {order.shipping_address?.address || 'Retiro / Dirección Estándar'}
                            </p>
                            <p className="text-gray-500">
                                {order.shipping_address?.city}
                                {order.shipping_address?.state ? `, ${order.shipping_address?.state}` : ''}
                            </p>
                        </div>

                        <div className="space-y-1">
                            <span className="font-bold text-gray-400 uppercase tracking-wider block text-[10px]">
                                Método de Pago
                            </span>
                            <p className="font-bold text-gray-900 dark:text-white capitalize">
                                {order.payment_method?.replace('_', ' ')}
                            </p>
                            <p className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 capitalize">
                                Estado: {order.payment_status}
                            </p>
                        </div>
                    </div>

                    {/* Stores Breakdown & Packages */}
                    <div className="space-y-6">
                        <h3 className="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <HiOutlineDocumentText className="w-5 h-5 text-blue-600" />
                            Desglose de Paquetes por Tienda ({order.stores_count || order.stores_breakdown?.length || 0})
                        </h3>

                        {order.stores_breakdown && order.stores_breakdown.map((store, idx) => (
                            <div
                                key={store.tenant_id || idx}
                                className="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden"
                            >
                                <div className="p-3.5 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <HiOutlineBuildingStorefront className="w-4 h-4 text-purple-600" />
                                        <span className="text-xs font-bold text-gray-900 dark:text-white">
                                            {store.store_name}
                                        </span>
                                    </div>
                                    <span className="text-xs font-bold text-blue-600 dark:text-blue-400">
                                        Subtotal Tienda: ${store.subtotal.toFixed(2)}
                                    </span>
                                </div>

                                <div className="p-4 divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                                    {store.items.map(item => (
                                        <div key={item.id} className="py-2.5 first:pt-0 last:pb-0 flex justify-between items-center">
                                            <div>
                                                <span className="font-bold text-gray-900 dark:text-white">
                                                    {item.quantity}x {item.product_name}
                                                </span>
                                                {item.sku && <span className="text-gray-400 ml-2">(SKU: {item.sku})</span>}
                                            </div>
                                            <div className="text-right font-black text-gray-900 dark:text-white">
                                                ${item.total.toFixed(2)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Total Summary Table */}
                    <div className="pt-6 border-t border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="text-xs text-gray-400">
                            * Los paquetes serán despachados directamente por cada tienda oficial.
                        </div>

                        <div className="text-right space-y-1">
                            <div className="text-xs text-gray-500">
                                Subtotal Consolidado: <strong>${order.subtotal.toFixed(2)} USD</strong>
                            </div>
                            <div className="text-xl font-black text-gray-900 dark:text-white">
                                Total Pagado: <span className="text-blue-600 dark:text-blue-400">${order.total.toFixed(2)} USD</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Bottom Actions */}
                <div className="flex flex-col sm:flex-row items-center justify-center gap-4 print:hidden">
                    <button
                        onClick={handlePrint}
                        className="w-full sm:w-auto px-6 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-xs hover:bg-gray-50 flex items-center justify-center gap-2 transition"
                    >
                        <HiOutlinePrinter className="w-4 h-4" />
                        Imprimir / Guardar Factura
                    </button>

                    <Link
                        href="/marketplace"
                        className="w-full sm:w-auto px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 flex items-center justify-center gap-2 transition"
                    >
                        <HiOutlineShoppingBag className="w-4 h-4" />
                        Continuar Comprando
                    </Link>
                </div>
            </div>
        </>
    );
};

const CentralOrderConfirmationPage: React.FC<CentralOrderConfirmationPageProps> = (props) => {
    return (
        <CentralLayout>
            <CentralOrderConfirmationPageContent {...props} />
        </CentralLayout>
    );
};

export default CentralOrderConfirmationPage;
