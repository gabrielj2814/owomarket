import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CentralLayout from '@/components/layouts/CentralLayout';
import { useCentralCart } from '@/contexts/CentralCartContext';
import CentralMarketplaceServices, {
    CentralProductItem,
    MarketplaceProductFilterParams,
    TenantStoreItem,
} from '@/Services/CentralMarketplaceServices';
import {
    HiOutlineShoppingBag,
    HiOutlineMagnifyingGlass,
    HiOutlineBuildingStorefront,
    HiOutlineFunnel,
    HiOutlineArrowPath,
} from 'react-icons/hi2';

interface CentralCatalogPageProps {
    domain?: string;
    query?: MarketplaceProductFilterParams;
}

const CentralCatalogPageContent: React.FC<CentralCatalogPageProps> = ({ domain, query = {} }) => {
    const { addItem } = useCentralCart();

    const [products, setProducts] = useState<CentralProductItem[]>([]);
    const [stores, setStores] = useState<TenantStoreItem[]>([]);
    const [loading, setLoading] = useState(true);
    const [total, setTotal] = useState(0);
    const [currentPage, setCurrentPage] = useState(query.page || 1);
    const [lastPage, setLastPage] = useState(1);

    // Filter states
    const [search, setSearch] = useState(query.search || '');
    const [selectedCategory, setSelectedCategory] = useState(query.category || '');
    const [selectedTenant, setSelectedTenant] = useState(query.tenant_id || '');
    const [sortBy, setSortBy] = useState<'price_asc' | 'price_desc' | 'newest' | 'name'>(query.sort_by || 'newest');
    const [minPrice, setMinPrice] = useState<string>(query.min_price ? String(query.min_price) : '');
    const [maxPrice, setMaxPrice] = useState<string>(query.max_price ? String(query.max_price) : '');

    // Fetch stores list
    useEffect(() => {
        CentralMarketplaceServices.getStores().then(res => {
            if ((res.code === 200 || res.status === 'success') && Array.isArray(res.data)) {
                setStores(res.data);
            }
        });
    }, []);

    // Fetch products
    const fetchProducts = (page: number = 1) => {
        setLoading(true);
        const params: MarketplaceProductFilterParams = {
            search: search || undefined,
            category: selectedCategory || undefined,
            tenant_id: selectedTenant || undefined,
            sort_by: sortBy,
            min_price: minPrice ? Number(minPrice) : undefined,
            max_price: maxPrice ? Number(maxPrice) : undefined,
            page,
            per_page: 16,
        };

        CentralMarketplaceServices.getProducts(params).then(res => {
            if ((res.code === 200 || res.status === 'success') && res.data) {
                setProducts(res.data.products || []);
                setTotal(res.data.total || 0);
                setCurrentPage(res.data.current_page || 1);
                setLastPage(res.data.last_page || 1);
            }
            setLoading(false);
        });
    };

    useEffect(() => {
        fetchProducts(1);
    }, [selectedCategory, selectedTenant, sortBy]);

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchProducts(1);
    };

    const handleResetFilters = () => {
        setSearch('');
        setSelectedCategory('');
        setSelectedTenant('');
        setMinPrice('');
        setMaxPrice('');
        setSortBy('newest');
        CentralMarketplaceServices.getProducts({ page: 1, per_page: 16 }).then(res => {
            if ((res.code === 200 || res.status === 'success') && res.data) {
                setProducts(res.data.products || []);
                setTotal(res.data.total || 0);
                setCurrentPage(1);
                setLastPage(res.data.last_page || 1);
            }
        });
    };

    const handleAddToCart = (product: CentralProductItem, e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        const defaultImg =
            product.images && product.images.length > 0
                ? product.images[0].image_path
                : null;

        addItem({
            tenant_id: product.tenant_id,
            tenant_name: product.tenant_name || 'Tienda Asociada',
            tenant_domain: product.tenant_domain,
            product_id: product.tenant_product_id || product.id,
            product_name: product.name,
            slug: product.slug,
            price: product.price,
            quantity: 1,
            image: defaultImg,
            sku: product.sku,
        });
    };

    return (
        <>
            <Head title="Explorar Catálogo Central - OwOMarket" />

            <div className="space-y-6">
                {/* Header Title */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white">
                            Catálogo del Marketplace Central
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {total} {total === 1 ? 'producto encontrado' : 'productos encontrados'} en todas las tiendas oficiales
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <select
                            value={sortBy}
                            onChange={e => setSortBy(e.target.value as any)}
                            className="text-xs font-semibold bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="newest">Más recientes</option>
                            <option value="price_asc">Menor precio</option>
                            <option value="price_desc">Mayor precio</option>
                            <option value="name">Nombre (A-Z)</option>
                        </select>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    {/* Left Sidebar Filters */}
                    <div className="space-y-6">
                        <form onSubmit={handleFilterSubmit} className="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 space-y-5">
                            <div className="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                                <span className="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                                    <HiOutlineFunnel className="w-4 h-4 text-blue-600" /> Filtros
                                </span>
                                <button
                                    type="button"
                                    onClick={handleResetFilters}
                                    className="text-xs text-gray-400 hover:text-red-500 font-semibold"
                                >
                                    Limpiar
                                </button>
                            </div>

                            {/* Search Filter */}
                            <div className="space-y-1.5">
                                <label className="text-xs font-bold text-gray-700 dark:text-gray-300">Búsqueda</label>
                                <div className="relative">
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={e => setSearch(e.target.value)}
                                        placeholder="Nombre, SKU o marca..."
                                        className="w-full pl-8 pr-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                    />
                                    <HiOutlineMagnifyingGlass className="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" />
                                </div>
                            </div>

                            {/* Store Filter */}
                            <div className="space-y-1.5">
                                <label className="text-xs font-bold text-gray-700 dark:text-gray-300">Tienda / Vendedor</label>
                                <select
                                    value={selectedTenant}
                                    onChange={e => setSelectedTenant(e.target.value)}
                                    className="w-full py-2 px-3 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Todas las tiendas</option>
                                    {stores.map(st => (
                                        <option key={st.id} value={st.id}>
                                            {st.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Price Range */}
                            <div className="space-y-1.5">
                                <label className="text-xs font-bold text-gray-700 dark:text-gray-300">Rango de Precio ($)</label>
                                <div className="grid grid-cols-2 gap-2">
                                    <input
                                        type="number"
                                        value={minPrice}
                                        onChange={e => setMinPrice(e.target.value)}
                                        placeholder="Min"
                                        className="w-full px-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
                                    />
                                    <input
                                        type="number"
                                        value={maxPrice}
                                        onChange={e => setMaxPrice(e.target.value)}
                                        placeholder="Max"
                                        className="w-full px-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <button
                                type="submit"
                                className="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition shadow-sm"
                            >
                                Aplicar Filtros
                            </button>
                        </form>
                    </div>

                    {/* Products Grid / Results */}
                    <div className="lg:col-span-3 space-y-6">
                        {loading ? (
                            <div className="flex items-center justify-center py-24">
                                <HiOutlineArrowPath className="w-8 h-8 text-blue-600 animate-spin" />
                            </div>
                        ) : products.length === 0 ? (
                            <div className="text-center py-20 rounded-3xl border border-dashed border-gray-200 dark:border-gray-800 p-8 space-y-3">
                                <div className="w-16 h-16 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded-full flex items-center justify-center mx-auto">
                                    <HiOutlineShoppingBag className="w-8 h-8" />
                                </div>
                                <h3 className="font-bold text-base text-gray-900 dark:text-white">No se encontraron productos</h3>
                                <p className="text-xs text-gray-500 max-w-xs mx-auto">
                                    Intenta ajustar los términos de búsqueda o cambiar los filtros de tienda.
                                </p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 sm:gap-6">
                                {products.map(product => {
                                    const mainImage =
                                        product.images && product.images.length > 0
                                            ? product.images[0].image_path
                                            : null;

                                    return (
                                        <div
                                            key={product.id}
                                            className="group rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between"
                                        >
                                            <Link
                                                href={`/product/${product.slug}`}
                                                className="relative block aspect-square bg-gray-100 dark:bg-gray-800 overflow-hidden"
                                            >
                                                {mainImage ? (
                                                    <img
                                                        src={mainImage}
                                                        alt={product.name}
                                                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                                    />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center text-gray-400 font-bold text-sm">
                                                        OwOMarket
                                                    </div>
                                                )}

                                                <div className="absolute top-2 left-2 max-w-[85%]">
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/90 dark:bg-gray-900/90 text-gray-900 dark:text-white backdrop-blur-sm shadow-sm truncate">
                                                        <HiOutlineBuildingStorefront className="w-3 h-3 text-blue-600 flex-shrink-0" />
                                                        <span className="truncate">{product.tenant_name}</span>
                                                    </span>
                                                </div>
                                            </Link>

                                            <div className="p-4 space-y-2 flex-1 flex flex-col justify-between">
                                                <div>
                                                    {product.category_name && (
                                                        <span className="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                                                            {product.category_name}
                                                        </span>
                                                    )}
                                                    <Link href={`/product/${product.slug}`}>
                                                        <h3 className="text-xs sm:text-sm font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition line-clamp-2 leading-snug">
                                                            {product.name}
                                                        </h3>
                                                    </Link>
                                                </div>

                                                <div className="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2">
                                                    <div>
                                                        <span className="text-xs text-gray-400 block font-normal">Precio:</span>
                                                        <span className="text-sm sm:text-base font-black text-gray-900 dark:text-white">
                                                            ${product.price.toFixed(2)}
                                                        </span>
                                                    </div>

                                                    <button
                                                        onClick={(e) => handleAddToCart(product, e)}
                                                        className="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600 dark:hover:text-white transition shadow-sm"
                                                        title="Agregar al carrito multi-tienda"
                                                    >
                                                        <HiOutlineShoppingBag className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}

                        {/* Pagination */}
                        {lastPage > 1 && (
                            <div className="flex items-center justify-center gap-2 pt-6">
                                <button
                                    disabled={currentPage <= 1}
                                    onClick={() => fetchProducts(currentPage - 1)}
                                    className="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 dark:border-gray-700 disabled:opacity-40"
                                >
                                    Anterior
                                </button>
                                <span className="text-xs font-semibold text-gray-500">
                                    Página {currentPage} de {lastPage}
                                </span>
                                <button
                                    disabled={currentPage >= lastPage}
                                    onClick={() => fetchProducts(currentPage + 1)}
                                    className="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 dark:border-gray-700 disabled:opacity-40"
                                >
                                    Siguiente
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
};

const CentralCatalogPage: React.FC<CentralCatalogPageProps> = (props) => {
    return (
        <CentralLayout>
            <CentralCatalogPageContent {...props} />
        </CentralLayout>
    );
};

export default CentralCatalogPage;
