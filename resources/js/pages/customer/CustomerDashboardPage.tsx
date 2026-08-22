import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CustomerPortalServices, {
    CustomerOrderData,
    CustomerCouponData,
    CustomerWishlistItemData,
} from '@/Services/CustomerPortalServices';
import OrderTrackingTimeline from '@/components/ui/storefront/OrderTrackingTimeline';
import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';
import {
    HiOutlineShoppingBag,
    HiOutlineTicket,
    HiOutlineHeart,
    HiOutlineDocumentText,
    HiOutlineArrowRight,
    HiOutlineTruck,
    HiOutlineMapPin,
    HiOutlineSparkles,
} from 'react-icons/hi2';

export const CustomerDashboardPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const [orders, setOrders] = useState<CustomerOrderData[]>([]);
    const [coupons, setCoupons] = useState<CustomerCouponData[]>([]);
    const [wishlist, setWishlist] = useState<CustomerWishlistItemData[]>([]);
    const [loading, setLoading] = useState(true);
    // Hallazgo N35: un error de red era indistinguible de «no tienes nada».
    const [loadError, setLoadError] = useState(false);

    useEffect(() => {
        if (!customer?.id) return;

        Promise.all([
            CustomerPortalServices.getOrders(customer.id, { limit: 5 }),
            CustomerPortalServices.getCoupons(customer.id),
            CustomerPortalServices.getWishlist(customer.id),
        ])
            .then(([ordersRes, couponsRes, wishlistRes]) => {
                if (ordersRes?.data) setOrders(ordersRes.data);
                if (couponsRes?.data) setCoupons(couponsRes.data);
                if (wishlistRes?.data) setWishlist(wishlistRes.data);
            })
            .catch(() => setLoadError(true))
            .finally(() => setLoading(false));
    }, [customer?.id]);

    const activeOrder = orders.find(o => o.status === 'processing' || o.status === 'paid' || o.status === 'pending');

    return (
        <CustomerAccountLayout
            title="Panel de Control"
            description="Visualiza el estado de tus pedidos en curso, facturas y promociones disponibles."
        >
            {loadError && <PortalLoadError />}

            <Head title="Mi Cuenta - Resumen" />

            {/* Quick Metrics Grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div className="p-5 rounded-3xl bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800/80 shadow-sm flex items-center gap-4">
                    <div className="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-blue-600 flex items-center justify-center">
                        <HiOutlineShoppingBag className="w-6 h-6" />
                    </div>
                    <div>
                        <span className="text-xs text-gray-500 dark:text-gray-400 font-medium block">Total Pedidos</span>
                        <span className="text-xl font-black text-gray-900 dark:text-white">{orders.length}</span>
                    </div>
                </div>

                <div className="p-5 rounded-3xl bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800/80 shadow-sm flex items-center gap-4">
                    <div className="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center">
                        <HiOutlineTicket className="w-6 h-6" />
                    </div>
                    <div>
                        <span className="text-xs text-gray-500 dark:text-gray-400 font-medium block">Cupones Disponibles</span>
                        <span className="text-xl font-black text-gray-900 dark:text-white">{coupons.length}</span>
                    </div>
                </div>

                <div className="p-5 rounded-3xl bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800/80 shadow-sm flex items-center gap-4">
                    <div className="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center">
                        <HiOutlineHeart className="w-6 h-6" />
                    </div>
                    <div>
                        <span className="text-xs text-gray-500 dark:text-gray-400 font-medium block">Favoritos Guardados</span>
                        <span className="text-xl font-black text-gray-900 dark:text-white">{wishlist.length}</span>
                    </div>
                </div>

                <div className="p-5 rounded-3xl bg-white dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800/80 shadow-sm flex items-center gap-4">
                    <div className="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 flex items-center justify-center">
                        <HiOutlineSparkles className="w-6 h-6" />
                    </div>
                    <div>
                        <span className="text-xs text-gray-500 dark:text-gray-400 font-medium block">Estado OwO Pass</span>
                        <span className="text-xs font-black text-purple-600 dark:text-purple-400 uppercase tracking-wide">Activo Global</span>
                    </div>
                </div>
            </div>

            {/* Active Order Spotlight */}
            {activeOrder && (
                <div className="mb-8">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <HiOutlineTruck className="w-5 h-5 text-blue-600" />
                            Seguimiento de tu Pedido Activo
                        </h3>
                        <Link
                            href={`/account/orders`}
                            className="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1"
                        >
                            Ver todos <HiOutlineArrowRight className="w-3.5 h-3.5" />
                        </Link>
                    </div>

                    <OrderTrackingTimeline
                        currentStep={activeOrder.status === 'completed' ? 5 : activeOrder.status === 'processing' ? 3 : 2}
                        courier={activeOrder.payment_details?.bank_origin ? 'MRW Envíos' : 'Zoom Envíos'}
                        trackingNumber={`OWO-${activeOrder.order_number.replace('ORD-', '')}`}
                        trackingUrl={`https://tracking.owomarket.com?guide=OWO-${activeOrder.order_number.replace('ORD-', '')}`}
                        timeline={[
                            {
                                step: 1,
                                key: 'placed',
                                title: 'Pedido Registrado',
                                description: `Orden ${activeOrder.order_number} creada.`,
                                timestamp: activeOrder.created_at,
                                is_completed: true,
                                is_current: activeOrder.payment_status !== 'paid',
                            },
                            {
                                step: 2,
                                key: 'paid',
                                title: 'Pago Confirmado',
                                description: activeOrder.payment_status === 'paid' ? 'Pago verificado.' : 'Esperando verificación.',
                                timestamp: activeOrder.payment_status === 'paid' ? activeOrder.created_at : null,
                                is_completed: activeOrder.payment_status === 'paid',
                                is_current: activeOrder.payment_status === 'paid' && activeOrder.status !== 'processing',
                            },
                            {
                                step: 3,
                                key: 'processing',
                                title: 'En Preparación',
                                description: 'Las tiendas están empacando tus productos.',
                                is_completed: activeOrder.status === 'processing' || activeOrder.status === 'completed',
                                is_current: activeOrder.status === 'processing',
                            },
                            {
                                step: 4,
                                key: 'in_transit',
                                title: 'Despachado',
                                description: 'En camino con la empresa de encomienda.',
                                is_completed: false,
                                is_current: false,
                            },
                            {
                                step: 5,
                                key: 'delivered',
                                title: 'Entregado',
                                description: 'Paquete recibido satisfactoriamente.',
                                is_completed: false,
                                is_current: false,
                            },
                        ]}
                    />
                </div>
            )}

            {/* Recent Orders & Wishlist Section */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {/* Recent Purchases */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-black text-gray-900 dark:text-white">
                            Compras Recientes
                        </h3>
                        <Link href="/account/orders" className="text-xs font-bold text-blue-600 hover:text-blue-700">
                            Ver historial
                        </Link>
                    </div>

                    {orders.length === 0 ? (
                        <div className="text-center py-8 text-gray-400">
                            <HiOutlineShoppingBag className="w-8 h-8 mx-auto mb-2 opacity-50" />
                            <p className="text-xs">Aún no has realizado pedidos.</p>
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-100 dark:divide-gray-800">
                            {orders.slice(0, 3).map(order => (
                                <div key={order.id} className="py-3 flex items-center justify-between">
                                    <div>
                                        <span className="text-xs font-bold text-gray-900 dark:text-white block">
                                            {order.order_number}
                                        </span>
                                        <span className="text-[11px] text-gray-400">
                                            {order.created_at ? order.created_at.substring(0, 10) : 'Reciente'}
                                        </span>
                                    </div>
                                    <div className="text-right">
                                        <CurrencyPriceDisplay priceUsd={order.total} size="sm" showVes={true} showBcvLabel={false} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Favorite Products */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-black text-gray-900 dark:text-white">
                            Favoritos Guardados
                        </h3>
                        <Link href="/account/wishlist" className="text-xs font-bold text-blue-600 hover:text-blue-700">
                            Ver todos
                        </Link>
                    </div>

                    {wishlist.length === 0 ? (
                        <div className="text-center py-8 text-gray-400">
                            <HiOutlineHeart className="w-8 h-8 mx-auto mb-2 opacity-50" />
                            <p className="text-xs">No tienes productos en tu lista de deseos.</p>
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-100 dark:divide-gray-800">
                            {wishlist.slice(0, 3).map(item => (
                                <div key={item.id} className="py-3 flex items-center justify-between">
                                    <span className="text-xs font-bold text-gray-900 dark:text-white truncate max-w-[200px]">
                                        {item.product_name}
                                    </span>
                                    <CurrencyPriceDisplay priceUsd={item.product_price} size="sm" showVes={true} showBcvLabel={false} />
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </CustomerAccountLayout>
    );
};

export default CustomerDashboardPage;
