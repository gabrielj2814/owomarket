import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import Dashboard from '@/components/layouts/Dashboard';
import TenantOwnerNavTabs from '@/components/tenant/TenantOwnerNavTabs';
import {
    HiOutlineCube,
    HiOutlineEye,
    HiOutlineEyeSlash,
    HiOutlineMagnifyingGlass,
    HiOutlineSparkles,
    HiOutlineArrowPath,
    HiOutlineBuildingStorefront,
} from 'react-icons/hi2';
import axios from 'axios';

interface ProductItem {
    id: string;
    tenant_id: string;
    tenant_name?: string;
    tenant?: { id: string; name: string; slug: string };
    name: string;
    sku: string | null;
    slug: string;
    price: number;
    stock: number;
    is_visible: boolean;
    main_image_url: string | null;
    category?: { id: string; name: string };
}

interface CatalogData {
    tenants: Array<{ id: string; name: string; slug: string }>;
    products: ProductItem[];
    pagination: {
        total: number;
        current_page: number;
        last_page: number;
        per_page: number;
    };
    metrics: {
        total_products: number;
        published_in_central: number;
        paused_in_central: number;
    };
}

interface TenantOwnerCentralCatalogPageProps {
    title?: string;
    user_id: string;
    catalog: CatalogData;
}

export const TenantOwnerCentralCatalogPage: React.FC<TenantOwnerCentralCatalogPageProps> = ({
    user_id,
    catalog: initialCatalog,
}) => {
    const [catalog, setCatalog] = useState<CatalogData>(initialCatalog);
    const [selectedTenant, setSelectedTenant] = useState<string>('');
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [loadingMap, setLoadingMap] = useState<Record<string, boolean>>({});
    const [actionMessage, setActionMessage] = useState<string | null>(null);

    const handleTogglePublication = async (productId: string, currentStatus: boolean) => {
        setLoadingMap(prev => ({ ...prev, [productId]: true }));
        setActionMessage(null);

        try {
            const response = await axios.post(`/tenant/owner/api/products/${productId}/toggle-marketplace`, {
                user_id,
                status: !currentStatus,
            });

            if (response.data?.status === 'success') {
                const newStatus = response.data.data.is_visible;
                setCatalog(prev => ({
                    ...prev,
                    products: prev.products.map(p => p.id === productId ? { ...p, is_visible: newStatus } : p),
                    metrics: {
                        ...prev.metrics,
                        published_in_central: newStatus ? prev.metrics.published_in_central + 1 : prev.metrics.published_in_central - 1,
                        paused_in_central: newStatus ? prev.metrics.paused_in_central - 1 : prev.metrics.paused_in_central + 1,
                    },
                }));
                setActionMessage(response.data.message);
            }
        } catch (error: any) {
            setActionMessage(error?.response?.data?.message || 'Error al cambiar estado de publicación');
        } finally {
            setLoadingMap(prev => ({ ...prev, [productId]: false }));
        }
    };

    const handleFilterChange = async (tenantId: string, search: string) => {
        try {
            const response = await axios.get('/tenant/owner/api/products', {
                params: {
                    user_id,
                    tenant_id: tenantId || undefined,
                    search: search || undefined,
                },
            });

            if (response.data?.status === 'success') {
                setCatalog(prev => ({
                    ...prev,
                    products: response.data.data.products,
                    pagination: response.data.data.pagination,
                    metrics: response.data.data.metrics,
                }));
            }
        } catch (error) {}
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title="Publicador de Catálogo Central - OwOMarket" />

            <div className="p-4 sm:p-6 space-y-6">
                <TenantOwnerNavTabs userId={user_id} activeTab="catalog" />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <h1 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <HiOutlineCube className="w-7 h-7 text-purple-600 dark:text-purple-400" />
                            Publicador & Sincronizador Central
                        </h1>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Controla qué productos de tus tiendas están visibles en el Marketplace Central. La sincronización de stock es automática y bidireccional.
                        </p>
                    </div>
                </div>

                {/* Feedback Message */}
                {actionMessage && (
                    <div className="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 text-xs font-semibold">
                        {actionMessage}
                    </div>
                )}

                {/* Metrics */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <span className="text-[11px] font-bold uppercase tracking-wider text-gray-400">
                            Total Productos Registrados
                        </span>
                        <div className="text-2xl font-black text-gray-900 dark:text-white mt-1">
                            {catalog.metrics.total_products}
                        </div>
                    </div>

                    <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <span className="text-[11px] font-bold uppercase tracking-wider text-emerald-500">
                            Publicados en Marketplace Central
                        </span>
                        <div className="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                            {catalog.metrics.published_in_central}
                        </div>
                    </div>

                    <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <span className="text-[11px] font-bold uppercase tracking-wider text-amber-500">
                            Pausados en Marketplace Central
                        </span>
                        <div className="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
                            {catalog.metrics.paused_in_central}
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="flex flex-col sm:flex-row items-center gap-3 bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-gray-700">
                    <div className="relative flex-1 w-full">
                        <HiOutlineMagnifyingGlass className="w-4 h-4 text-gray-400 absolute left-3 top-3" />
                        <input
                            type="text"
                            placeholder="Buscar por nombre, código SKU o slug..."
                            value={searchQuery}
                            onChange={e => {
                                setSearchQuery(e.target.value);
                                handleFilterChange(selectedTenant, e.target.value);
                            }}
                            className="w-full pl-9 pr-4 py-2 rounded-xl text-xs border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white"
                        />
                    </div>

                    <select
                        value={selectedTenant}
                        onChange={e => {
                            setSelectedTenant(e.target.value);
                            handleFilterChange(e.target.value, searchQuery);
                        }}
                        className="w-full sm:w-64 p-2 rounded-xl text-xs border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white font-medium"
                    >
                        <option value="">Todas las Tiendas ({catalog.tenants.length})</option>
                        {catalog.tenants.map(t => (
                            <option key={t.id} value={t.id}>{t.name}</option>
                        ))}
                    </select>
                </div>

                {/* Products Table */}
                <div className="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                    {catalog.products.length === 0 ? (
                        <div className="text-center py-12 text-gray-400 text-xs">
                            No se encontraron productos con los filtros seleccionados.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="text-[11px] font-black uppercase tracking-wider text-gray-400 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                    <tr>
                                        <th className="py-3 px-4 rounded-l-xl">Producto</th>
                                        <th className="py-3 px-4">Tienda</th>
                                        <th className="py-3 px-4 text-right">Precio USD</th>
                                        <th className="py-3 px-4 text-center">Stock</th>
                                        <th className="py-3 px-4 text-center">Estado Central</th>
                                        <th className="py-3 px-4 rounded-r-xl text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                                    {catalog.products.map(product => {
                                        const isToggling = !!loadingMap[product.id];

                                        return (
                                            <tr key={product.id} className="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 overflow-hidden shrink-0 flex items-center justify-center font-bold text-gray-400 text-xs">
                                                            {product.main_image_url ? (
                                                                <img src={product.main_image_url} alt={product.name} className="w-full h-full object-cover" />
                                                            ) : (
                                                                product.name.substring(0, 2).toUpperCase()
                                                            )}
                                                        </div>
                                                        <div>
                                                            <div className="font-bold text-gray-900 dark:text-white">
                                                                {product.name}
                                                            </div>
                                                            <span className="text-[10px] text-gray-400">
                                                                SKU: {product.sku || 'N/A'}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4 font-semibold text-gray-600 dark:text-gray-300">
                                                    {product.tenant?.name || product.tenant_id}
                                                </td>
                                                <td className="py-4 px-4 text-right font-black text-gray-900 dark:text-white">
                                                    ${product.price.toFixed(2)}
                                                </td>
                                                <td className="py-4 px-4 text-center font-bold">
                                                    <span className={`px-2 py-0.5 rounded-full text-[10px] ${
                                                        product.stock > 5
                                                            ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300'
                                                            : product.stock > 0
                                                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                                            : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                                                    }`}>
                                                        {product.stock} un.
                                                    </span>
                                                </td>
                                                <td className="py-4 px-4 text-center">
                                                    {product.is_visible ? (
                                                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                                                            <HiOutlineSparkles className="w-3 h-3" /> Visible en Central
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                            Solo Tienda Privada
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-4 px-4 text-center">
                                                    <button
                                                        onClick={() => handleTogglePublication(product.id, product.is_visible)}
                                                        disabled={isToggling}
                                                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-[11px] transition shadow-sm ${
                                                            product.is_visible
                                                                ? 'bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-950/80 dark:hover:bg-amber-900 dark:text-amber-300'
                                                                : 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-500/20'
                                                        }`}
                                                    >
                                                        {isToggling ? (
                                                            <HiOutlineArrowPath className="w-3.5 h-3.5 animate-spin" />
                                                        ) : product.is_visible ? (
                                                            <>
                                                                <HiOutlineEyeSlash className="w-3.5 h-3.5" />
                                                                <span>Pausar en Central</span>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <HiOutlineEye className="w-3.5 h-3.5" />
                                                                <span>Publicar en Central</span>
                                                            </>
                                                        )}
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </Dashboard>
    );
};

export default TenantOwnerCentralCatalogPage;
