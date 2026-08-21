import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CentralLayout from '@/components/layouts/CentralLayout';
import { useCentralCart } from '@/contexts/CentralCartContext';
import CentralMarketplaceServices, {
    CentralProductItem,
    MarketplaceHomeData,
    TenantStoreItem,
} from '@/Services/CentralMarketplaceServices';
import {
    HiOutlineShoppingBag,
    HiOutlineMagnifyingGlass,
    HiOutlineBuildingStorefront,
    HiOutlineSparkles,
    HiOutlineShieldCheck,
    HiOutlineDevicePhoneMobile,
    HiOutlineCurrencyDollar,
    HiOutlineArrowTopRightOnSquare,
    HiOutlineCheckCircle,
} from 'react-icons/hi2';

interface CentralHomePageProps {
    domain?: string;
    initial_data?: MarketplaceHomeData;
}

const CentralHomePageContent: React.FC<CentralHomePageProps> = ({ domain, initial_data }) => {
    const { addItem } = useCentralCart();
    const [data, setData] = useState<MarketplaceHomeData>(
        initial_data || {
            featured_stores: [],
            featured_products: [],
            recent_products: [],
            categories: [],
        }
    );
    const [loading, setLoading] = useState(!initial_data);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState<string | null>(null);

    useEffect(() => {
        if (!initial_data) {
            CentralMarketplaceServices.getHomeData().then(res => {
                if ((res.code === 200 || res.status === 'success') && res.data) {
                    setData(res.data);
                }
                setLoading(false);
            });
        }
    }, [initial_data]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            window.location.href = `/marketplace?search=${encodeURIComponent(searchQuery.trim())}`;
        }
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

    // Filter displayed products if category selected
    const displayedProducts = selectedCategory
        ? data.recent_products.filter(p => p.category_name === selectedCategory)
        : data.recent_products;

    return (
        <>
            <Head title="OwOMarket Central - Marketplace Multi-Tienda" />

            <div className="space-y-12 sm:space-y-16">
                {/* 1. HERO SECTION */}
                <div className="relative rounded-3xl overflow-hidden bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-950 text-white shadow-2xl p-6 sm:p-12 lg:p-16">
                    {/* Glowing background shapes */}
                    <div className="absolute top-0 right-0 -mt-12 -mr-12 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl pointer-events-none" />
                    <div className="absolute bottom-0 left-0 -mb-12 -ml-12 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl pointer-events-none" />

                    <div className="relative z-10 max-w-3xl space-y-6">
                        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-semibold text-blue-200">
                            <HiOutlineSparkles className="w-4 h-4 text-yellow-300" />
                            <span>El Marketplace Central de Venezuela</span>
                        </div>

                        <h1 className="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight">
                            Compra en múltiples tiendas,{' '}
                            <span className="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 via-teal-300 to-purple-300">
                                paga en una sola factura.
                            </span>
                        </h1>

                        <p className="text-sm sm:text-base text-gray-300 leading-relaxed max-w-2xl">
                            Descubre miles de productos de tiendas oficiales independientes. Combina tus compras de diferentes comercios en un solo carrito y paga con <strong>Pago Móvil</strong> o <strong>Binance Pay</strong>.
                        </p>

                        {/* Search in Hero */}
                        <form onSubmit={handleSearchSubmit} className="flex flex-col sm:flex-row gap-2 pt-2">
                            <div className="relative flex-1">
                                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                    <HiOutlineMagnifyingGlass className="w-5 h-5" />
                                </div>
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={e => setSearchQuery(e.target.value)}
                                    placeholder="¿Qué estás buscando hoy? Zapatos, laptops, ropa..."
                                    className="w-full pl-12 pr-4 py-3.5 bg-white/95 text-gray-900 placeholder-gray-500 rounded-2xl border-0 shadow-lg focus:ring-2 focus:ring-blue-400 text-sm font-medium"
                                />
                            </div>
                            <button
                                type="submit"
                                className="px-8 py-3.5 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold rounded-2xl shadow-lg shadow-blue-500/30 transition text-sm flex items-center justify-center gap-2"
                            >
                                <HiOutlineMagnifyingGlass className="w-4 h-4" />
                                Explorar
                            </button>
                        </form>

                        {/* Quick category badges */}
                        {data.categories && data.categories.length > 0 && (
                            <div className="flex flex-wrap items-center gap-2 pt-2 text-xs text-gray-300">
                                <span className="text-gray-400">Populares:</span>
                                {data.categories.slice(0, 5).map(cat => (
                                    <Link
                                        key={cat.name}
                                        href={`/marketplace?category=${encodeURIComponent(cat.name)}`}
                                        className="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/20 backdrop-blur-sm transition border border-white/10"
                                    >
                                        {cat.name}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* 2. TIENDAS DESTACADAS (FEATURED STORES) */}
                {data.featured_stores && data.featured_stores.length > 0 && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                                    <HiOutlineBuildingStorefront className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    Tiendas Oficiales Verificadas
                                </h2>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Visita las tiendas independientes asociadas al ecosistema OwOMarket
                                </p>
                            </div>
                            <Link
                                href="/marketplace"
                                className="text-xs font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center gap-1"
                            >
                                Ver Todo Catálogo →
                            </Link>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            {data.featured_stores.map(store => (
                                <div
                                    key={store.id}
                                    className="group relative rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden flex flex-col justify-between"
                                >
                                    <div className="space-y-3">
                                        <div className="flex items-center gap-3">
                                            {store.logo ? (
                                                <img
                                                    src={store.logo}
                                                    alt={store.name}
                                                    className="w-12 h-12 rounded-xl object-cover border border-gray-200 dark:border-gray-700 shadow-sm"
                                                />
                                            ) : (
                                                <div className="w-12 h-12 rounded-xl bg-gradient-to-tr from-purple-500 to-indigo-600 text-white font-bold text-base flex items-center justify-center shadow-sm">
                                                    {store.name.substring(0, 2).toUpperCase()}
                                                </div>
                                            )}
                                            <div className="flex-1 min-w-0">
                                                <h3 className="font-bold text-sm text-gray-900 dark:text-white truncate">
                                                    {store.name}
                                                </h3>
                                                <span className="text-[10px] text-green-600 dark:text-green-400 font-semibold flex items-center gap-1">
                                                    <HiOutlineCheckCircle className="w-3.5 h-3.5 inline" /> Tienda Verificada
                                                </span>
                                            </div>
                                        </div>

                                        <p className="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                            {store.description || 'Vendedor oficial registrado en OwOMarket.'}
                                        </p>
                                    </div>

                                    <div className="pt-4 border-t border-gray-100 dark:border-gray-800 mt-4 flex items-center justify-between">
                                        <span className="text-[11px] font-medium text-gray-400">
                                            {store.products_count || 0} productos
                                        </span>
                                        <Link
                                            href={`/marketplace?tenant_id=${store.id}`}
                                            className="inline-flex items-center gap-1 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline"
                                        >
                                            Ver Productos
                                            <HiOutlineArrowTopRightOnSquare className="w-3.5 h-3.5" />
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* 3. CATEGORY PILLS FILTER */}
                {data.categories && data.categories.length > 0 && (
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                                Filtrar por Categoría
                            </h2>
                            {selectedCategory && (
                                <button
                                    onClick={() => setSelectedCategory(null)}
                                    className="text-xs text-red-500 font-semibold hover:underline"
                                >
                                    Limpiar filtro
                                </button>
                            )}
                        </div>

                        <div className="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                            <button
                                onClick={() => setSelectedCategory(null)}
                                className={`px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition ${
                                    selectedCategory === null
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100'
                                }`}
                            >
                                Todas las Categorías
                            </button>
                            {data.categories.map(cat => (
                                <button
                                    key={cat.name}
                                    onClick={() => setSelectedCategory(cat.name)}
                                    className={`px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center gap-1.5 ${
                                        selectedCategory === cat.name
                                            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                            : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100'
                                    }`}
                                >
                                    {cat.name}
                                    <span className="text-[10px] opacity-70 px-1.5 py-0.2 rounded-full bg-black/10 dark:bg-white/10">
                                        {cat.count}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                {/* 4. PRODUCT GRID */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">
                                {selectedCategory ? `Productos en ${selectedCategory}` : 'Novedades del Marketplace'}
                            </h2>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Productos disponibles para envío inmediato desde tiendas oficiales
                            </p>
                        </div>
                        <Link
                            href="/marketplace"
                            className="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-800 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        >
                            Ver Todo el Catálogo
                        </Link>
                    </div>

                    {displayedProducts.length === 0 ? (
                        <div className="rounded-3xl border border-dashed border-gray-300 dark:border-gray-800 p-12 text-center space-y-3">
                            <div className="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 text-blue-500 rounded-full flex items-center justify-center mx-auto">
                                <HiOutlineShoppingBag className="w-8 h-8" />
                            </div>
                            <h3 className="font-bold text-base text-gray-900 dark:text-white">No se encontraron productos</h3>
                            <p className="text-xs text-gray-500 max-w-sm mx-auto">
                                Las tiendas están preparando sus catálogos. Explora otras categorías o regresa pronto.
                            </p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                            {displayedProducts.map(product => {
                                const mainImage =
                                    product.images && product.images.length > 0
                                        ? product.images[0].image_path
                                        : null;

                                return (
                                    <div
                                        key={product.id}
                                        className="group rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between"
                                    >
                                        {/* Image Container */}
                                        <Link
                                            href={`/product/${product.id}`}
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

                                            {/* Store badge overlay */}
                                            <div className="absolute top-2 left-2 max-w-[85%]">
                                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/90 dark:bg-gray-900/90 text-gray-900 dark:text-white backdrop-blur-sm shadow-sm truncate">
                                                    <HiOutlineBuildingStorefront className="w-3 h-3 text-blue-600 flex-shrink-0" />
                                                    <span className="truncate">{product.tenant_name}</span>
                                                </span>
                                            </div>
                                        </Link>

                                        {/* Content */}
                                        <div className="p-4 space-y-2 flex-1 flex flex-col justify-between">
                                            <div>
                                                {product.category_name && (
                                                    <span className="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                                                        {product.category_name}
                                                    </span>
                                                )}
                                                <Link href={`/product/${product.id}`}>
                                                    <h3 className="text-xs sm:text-sm font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition line-clamp-2 leading-snug">
                                                        {product.name}
                                                    </h3>
                                                </Link>
                                            </div>

                                            {/* Price & Add to Cart */}
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
                </div>

                {/* 5. BENEFITS SECTION */}
                <div className="rounded-3xl border border-gray-200 dark:border-gray-800 bg-gradient-to-r from-blue-50/50 via-indigo-50/50 to-purple-50/50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-900 p-8 sm:p-12">
                    <div className="text-center max-w-2xl mx-auto space-y-2 mb-8">
                        <h2 className="text-2xl font-black text-gray-900 dark:text-white">
                            ¿Por qué comprar en OwOMarket?
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            La experiencia de compra más cómoda, moderna y segura del comercio electrónico
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="p-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3 shadow-sm">
                            <div className="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <HiOutlineShoppingBag className="w-6 h-6" />
                            </div>
                            <h3 className="font-bold text-sm text-gray-900 dark:text-white">
                                Carrito Multi-Tienda Unificado
                            </h3>
                            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                Elige artículos de diferentes vendedores y recíbelos organizados. Paga una sola vez y nosotros enrutamos las órdenes a cada tienda.
                            </p>
                        </div>

                        <div className="p-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3 shadow-sm">
                            <div className="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 flex items-center justify-center">
                                <HiOutlineDevicePhoneMobile className="w-6 h-6" />
                            </div>
                            <h3 className="font-bold text-sm text-gray-900 dark:text-white">
                                Pago Móvil y Binance Pay
                            </h3>
                            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                Paga en Bolívares con la tasa oficial BCV de forma instantánea o utiliza USDT a través de código QR y Pay ID con Binance.
                            </p>
                        </div>

                        <div className="p-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3 shadow-sm">
                            <div className="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <HiOutlineSparkles className="w-6 h-6" />
                            </div>
                            <h3 className="font-bold text-sm text-gray-900 dark:text-white">
                                OwO Pass SSO Universal
                            </h3>
                            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                Una sola cuenta para todo el ecosistema. Inicia sesión una vez y navega libremente por todas las tiendas de la plataforma sin crear cuentas extra.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

const CentralHomePage: React.FC<CentralHomePageProps> = (props) => {
    return (
        <CentralLayout>
            <CentralHomePageContent {...props} />
        </CentralLayout>
    );
};

export default CentralHomePage;
