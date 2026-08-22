import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import { useCentralCart } from '@/contexts/CentralCartContext';
import CustomerPortalServices, { CustomerOrderData } from '@/Services/CustomerPortalServices';
import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';
import {
    HiOutlineShoppingBag,
    HiOutlineTruck,
    HiOutlineArrowPath,
    HiOutlineMagnifyingGlass,
    HiOutlineChevronRight,
    HiOutlineBuildingStorefront,
    HiOutlineArrowPathRoundedSquare,
} from 'react-icons/hi2';

export const CustomerOrdersPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const { addItem, setIsDrawerOpen } = useCentralCart();

    const [orders, setOrders] = useState<CustomerOrderData[]>([]);
    const [loading, setLoading] = useState(true);
    const [filterStatus, setFilterStatus] = useState<string>('all');
    const [searchQuery, setSearchQuery] = useState('');

    const loadOrders = () => {
        if (!customer?.id) return;
        setLoading(true);
        CustomerPortalServices.getOrders(customer.id, {
            status: filterStatus === 'all' ? undefined : filterStatus,
            search: searchQuery.trim() || undefined,
        })
            .then(res => {
                if (res.data) {
                    setOrders(res.data);
                }
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadOrders();
    }, [customer?.id, filterStatus]);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        loadOrders();
    };

    const handleReorder = (order: CustomerOrderData) => {
        if (!order.items || order.items.length === 0) return;
        order.items.forEach(item => {
            // Hallazgo G15: aqui se pasaba `tenant_name: item.tenant_id` y
            // `slug: item.product_id`, asi que el cajon del carrito mostraba el UUID de la
            // tienda como si fuera su nombre y el enlace al producto no llevaba a ninguna
            // parte. El backend ya envia los dos campos de verdad.
            addItem({
                tenant_id: item.tenant_id,
                tenant_name: item.tenant_name || 'Tienda',
                product_id: item.product_id,
                product_name: item.product_name,
                slug: item.product_slug || item.product_id,
                price: item.price,
                quantity: item.quantity,
            });
        });
        setIsDrawerOpen(true);
    };

    const statusBadge = (status: string) => {
        switch (status) {
            case 'completed':
                return <span className="px-2.5 py-1 bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 rounded-full text-[10px] font-black uppercase tracking-wider">Entregado</span>;
            case 'processing':
                return <span className="px-2.5 py-1 bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 rounded-full text-[10px] font-black uppercase tracking-wider">En Preparación</span>;
            case 'paid':
                return <span className="px-2.5 py-1 bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 rounded-full text-[10px] font-black uppercase tracking-wider">Pagado / Verificado</span>;
            case 'cancelled':
                return <span className="px-2.5 py-1 bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 rounded-full text-[10px] font-black uppercase tracking-wider">Cancelado</span>;
            default:
                return <span className="px-2.5 py-1 bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 rounded-full text-[10px] font-black uppercase tracking-wider">Pendiente de Pago</span>;
        }
    };

    return (
        <CustomerAccountLayout
            title="Mis Pedidos & Tracking"
            description="Revisa el historial de compras realizadas en las distintas tiendas y rastrea tus envíos en vivo."
        >
            <Head title="Mis Pedidos - OwOMarket" />

            {/* Filter Tabs & Search Bar */}
            <div className="bg-white dark:bg-gray-900 rounded-3xl p-4 mb-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                {/* Tabs */}
                <div className="flex items-center gap-1 overflow-x-auto pb-2 md:pb-0">
                    {[
                        { key: 'all', label: 'Todos' },
                        { key: 'paid', label: 'Pagados' },
                        { key: 'processing', label: 'En Preparación' },
                        { key: 'completed', label: 'Entregados' },
                        { key: 'cancelled', label: 'Cancelados' },
                    ].map(tab => (
                        <button
                            key={tab.key}
                            onClick={() => setFilterStatus(tab.key)}
                            className={`px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition ${
                                filterStatus === tab.key
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'
                            }`}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* Search */}
                <form onSubmit={handleSearch} className="relative min-w-[240px]">
                    <HiOutlineMagnifyingGlass className="w-4 h-4 text-gray-400 absolute left-3 top-3 pointer-events-none" />
                    <input
                        type="text"
                        value={searchQuery}
                        onChange={e => setSearchQuery(e.target.value)}
                        placeholder="Buscar por N° orden o producto..."
                        className="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                    />
                </form>
            </div>

            {/* Orders Listing */}
            {loading ? (
                <div className="text-center py-16 text-gray-400">
                    <HiOutlineArrowPath className="w-8 h-8 mx-auto mb-2 animate-spin text-blue-600" />
                    <p className="text-xs font-medium">Cargando pedidos...</p>
                </div>
            ) : orders.length === 0 ? (
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center border border-gray-200/80 dark:border-gray-800/80">
                    <HiOutlineShoppingBag className="w-12 h-12 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                    <h4 className="text-base font-bold text-gray-900 dark:text-white mb-1">
                        No se encontraron pedidos
                    </h4>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-6">
                        {filterStatus !== 'all' ? 'No hay pedidos con el estado seleccionado.' : 'Aún no has realizado compras en el marketplace.'}
                    </p>
                    <Link
                        href="/marketplace"
                        className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition"
                    >
                        Explorar Catálogo de Productos
                    </Link>
                </div>
            ) : (
                <div className="space-y-4">
                    {orders.map(order => (
                        <div
                            key={order.id}
                            className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80 hover:border-blue-500/50 transition"
                        >
                            {/* Order Header */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 gap-3">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm font-black text-gray-900 dark:text-white">
                                            {order.order_number}
                                        </span>
                                        {statusBadge(order.status)}
                                    </div>
                                    <span className="text-[11px] text-gray-400 mt-0.5 block">
                                        Fecha: {order.created_at ? order.created_at.substring(0, 10) : 'N/A'} • Pago: {order.payment_method === 'pago_movil' ? 'Pago Móvil' : 'Binance Pay'}
                                    </span>
                                </div>

                                <div className="text-left sm:text-right">
                                    <span className="text-xs text-gray-400 block">Monto Total:</span>
                                    <CurrencyPriceDisplay priceUsd={order.total} size="md" showVes={true} showBcvLabel={true} />
                                </div>
                            </div>

                            {/* Order Items Preview */}
                            {order.items && order.items.length > 0 && (
                                <div className="py-4 space-y-2">
                                    {order.items.map(item => (
                                        <div key={item.id} className="flex items-center justify-between text-xs">
                                            <div className="flex items-center gap-2">
                                                <HiOutlineBuildingStorefront className="w-4 h-4 text-blue-600" />
                                                <span className="text-gray-900 dark:text-white font-semibold">
                                                    {item.product_name}
                                                </span>
                                                <span className="text-gray-400 text-[11px]">
                                                    x{item.quantity}
                                                </span>
                                            </div>
                                            <span className="font-bold text-gray-700 dark:text-gray-300">
                                                ${item.total.toFixed(2)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Order Actions */}
                            <div className="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                                <div className="flex items-center gap-2">
                                    <button
                                        onClick={() => handleReorder(order)}
                                        className="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl flex items-center gap-1.5 transition"
                                    >
                                        <HiOutlineArrowPath className="w-3.5 h-3.5 text-blue-600" />
                                        Volver a Comprar (1-Clic)
                                    </button>

                                    {order.status === 'completed' && (
                                        <Link
                                            href="/account/returns"
                                            className="px-3 py-1.5 text-gray-500 hover:text-amber-600 text-xs font-semibold flex items-center gap-1 transition"
                                        >
                                            <HiOutlineArrowPathRoundedSquare className="w-3.5 h-3.5" />
                                            Solicitar Devolución
                                        </Link>
                                    )}
                                </div>

                                <div className="flex items-center gap-2">
                                    <a
                                        href={CustomerPortalServices.getInvoicePdfUrl(customer?.id || '', order.id)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="px-3 py-1.5 text-blue-600 hover:text-blue-700 text-xs font-bold"
                                    >
                                        Descargar Factura PDF
                                    </a>
                                    <Link
                                        href={`/account/orders/${order.id}`}
                                        className="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-1 transition"
                                    >
                                        <HiOutlineTruck className="w-4 h-4" />
                                        Ver Tracking & Detalle
                                        <HiOutlineChevronRight className="w-3.5 h-3.5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </CustomerAccountLayout>
    );
};

export default CustomerOrdersPage;
