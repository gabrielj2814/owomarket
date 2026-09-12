import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import { Badge, Button, Card, Spinner, TextInput } from 'flowbite-react';
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
    // Hallazgo N35: un error de red era indistinguible de «no tienes nada».
    const [loadError, setLoadError] = useState(false);
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
            .catch(() => setLoadError(true))
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

    /**
     * El estado, como insignia del portal.
     *
     * Antes eran seis `<span>` con la misma pastilla copiada. Ahora el aspecto lo pone
     * `portalTheme` y aqui solo queda la correspondencia estado -> color, que es la unica
     * decision que pertenece a esta pantalla.
     */
    const ESTADO: Record<string, { texto: string; color: string }> = {
        completed: { texto: 'Entregado', color: 'success' },
        processing: { texto: 'En Preparación', color: 'blue' },
        paid: { texto: 'Pagado / Verificado', color: 'indigo' },
        cancelled: { texto: 'Cancelado', color: 'failure' },
    };

    const statusBadge = (status: string) => {
        const { texto, color } = ESTADO[status] ?? { texto: 'Pendiente de Pago', color: 'warning' };

        return (
            <Badge color={color} size="xs" className="w-fit">
                {texto}
            </Badge>
        );
    };

    return (
        <CustomerAccountLayout
            title="Mis Pedidos & Tracking"
            description="Revisa el historial de compras realizadas en las distintas tiendas y rastrea tus envíos en vivo."
        >
            {loadError && <PortalLoadError />}

            <Head title="Mis Pedidos - OwOMarket" />

            {/* Filter Tabs & Search Bar */}
            <Card className="mb-6" theme={{ root: { children: 'flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 p-4' } }}>
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
                <form onSubmit={handleSearch} className="min-w-[240px]">
                    <TextInput
                        id="pedidos-busqueda"
                        icon={HiOutlineMagnifyingGlass}
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        placeholder="Buscar por N° orden o producto..."
                    />
                </form>
            </Card>

            {/* Orders Listing */}
            {loading ? (
                <div className="py-16 text-center">
                    <Spinner aria-label="Cargando pedidos" />
                    <p className="mt-2 text-xs font-medium text-gray-400">Cargando pedidos...</p>
                </div>
            ) : orders.length === 0 ? (
                <Card>
                    <div data-testid="pedidos-vacio" className="py-6 text-center">
                        <HiOutlineShoppingBag className="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-700" />
                        <h4 className="mb-1 text-base font-bold text-gray-900 dark:text-white">
                            No se encontraron pedidos
                        </h4>
                        <p className="mb-6 text-xs text-gray-500 dark:text-gray-400">
                            {filterStatus !== 'all'
                                ? 'No hay pedidos con el estado seleccionado.'
                                : 'Aún no has realizado compras en el marketplace.'}
                        </p>
                        <Button as={Link} href="/marketplace" color="primary" size="sm" className="mx-auto w-fit">
                            Explorar Catálogo de Productos
                        </Button>
                    </div>
                </Card>
            ) : (
                <div className="space-y-4">
                    {orders.map(order => (
                        <Card key={order.id} className="transition hover:border-blue-500/50">
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
                                    <Button color="light" size="xs" onClick={() => handleReorder(order)}>
                                        <HiOutlineArrowPath className="mr-1.5 h-3.5 w-3.5 text-blue-600" />
                                        Volver a Comprar (1-Clic)
                                    </Button>

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
                                    <Button as={Link} href={`/account/orders/${order.id}`} color="primary" size="xs">
                                        <HiOutlineTruck className="mr-1 h-4 w-4" />
                                        Ver Tracking & Detalle
                                        <HiOutlineChevronRight className="ml-1 h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    ))}
                </div>
            )}
        </CustomerAccountLayout>
    );
};

export default CustomerOrdersPage;
