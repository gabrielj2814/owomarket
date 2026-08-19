import React from 'react';
import StorefrontLayout from '@/components/layouts/StorefrontLayout';
import ProductCard from '@/components/ui/storefront/ProductCard';
import { StorefrontHomePageProps } from '@/types/models/Storefront';
import { Button } from 'flowbite-react';
import {
    HiArrowRight,
    HiFire,
    HiOutlineShoppingBag,
    HiSparkles,
    HiStar,
    HiTag,
    HiTruck,
} from 'react-icons/hi';

export default function TenantStorefrontHomePage({
    domain,
    store_settings,
    categories = [],
    featured_products = [],
    new_products = [],
    auth_user = null,
}: StorefrontHomePageProps) {
    const storeName = store_settings?.store_name || 'Mi Tienda Online';
    const bannerUrl = store_settings?.banner_url;

    return (
        <StorefrontLayout
            domain={domain}
            storeSettings={store_settings}
            categories={categories}
            authUser={auth_user}
        >
            <div className="space-y-12 sm:space-y-16">
                {/* 1. Hero Banner */}
                <section className="relative rounded-3xl overflow-hidden shadow-xl bg-gradient-to-r from-gray-900 via-blue-950 to-indigo-950 text-white min-h-[360px] sm:min-h-[440px] flex items-center">
                    {/* Background Banner Image with Overlay */}
                    {bannerUrl && (
                        <img
                            src={bannerUrl}
                            alt={storeName}
                            className="absolute inset-0 w-full h-full object-cover opacity-30 mix-blend-overlay"
                            onError={(e) => {
                                (e.target as HTMLImageElement).style.display = 'none';
                            }}
                        />
                    )}

                    {/* Ambient Glow */}
                    <div className="absolute -top-24 -left-24 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl pointer-events-none" />
                    <div className="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none" />

                    {/* Banner Content */}
                    <div className="relative z-10 max-w-2xl p-6 sm:p-12 space-y-4 sm:space-y-6">
                        <div className="inline-flex items-center gap-2 bg-blue-500/20 backdrop-blur-md border border-blue-400/30 text-blue-300 px-3.5 py-1 rounded-full text-xs font-bold tracking-wide uppercase">
                            <HiSparkles className="w-4 h-4 text-amber-300" />
                            <span>Bienvenido a {storeName}</span>
                        </div>

                        <h1 className="text-3xl sm:text-5xl font-black tracking-tight leading-tight sm:leading-none">
                            {store_settings?.seo_title || `La mejor experiencia de compra en ${storeName}`}
                        </h1>

                        <p className="text-sm sm:text-base text-gray-300 leading-relaxed max-w-xl">
                            {store_settings?.seo_description ||
                                'Descubre nuestro catálogo exclusivo con los mejores precios, garantía directa y despacho express a tu puerta.'}
                        </p>

                        <div className="flex flex-wrap gap-3 pt-2">
                            <Button
                                color="blue"
                                size="lg"
                                onClick={() => (window.location.href = '/catalog')}
                            >
                                <span className="flex items-center gap-2 font-bold">
                                    Ver Catálogo Completo
                                    <HiArrowRight className="w-5 h-5" />
                                </span>
                            </Button>
                            <Button
                                color="light"
                                size="lg"
                                onClick={() => (window.location.href = '/catalog?filter=on_sale')}
                            >
                                <span className="flex items-center gap-2 font-semibold">
                                    <HiFire className="w-5 h-5 text-red-500" />
                                    Ofertas Especiales
                                </span>
                            </Button>
                        </div>
                    </div>
                </section>

                {/* 2. Categories Grid */}
                {categories.length > 0 && (
                    <section className="space-y-6">
                        <div className="flex justify-between items-end">
                            <div>
                                <h2 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                                    Explora por Categoría
                                </h2>
                                <p className="text-sm text-gray-500 mt-1">
                                    Encuentra exactamente lo que buscas navegando por departamento
                                </p>
                            </div>
                            <a
                                href="/catalog"
                                className="text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline hidden sm:flex items-center gap-1"
                            >
                                Ver todas <HiArrowRight className="w-4 h-4" />
                            </a>
                        </div>

                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                            {categories.map((category) => (
                                <a
                                    key={category.id}
                                    href={`/catalog?category=${category.slug}`}
                                    className="group p-4 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl text-center shadow-sm hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all duration-300 flex flex-col items-center justify-center gap-2"
                                >
                                    <div className="w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                        {category.image ? (
                                            <img
                                                src={category.image}
                                                alt={category.name}
                                                className="w-10 h-10 object-contain"
                                            />
                                        ) : (
                                            <HiOutlineShoppingBag className="w-7 h-7" />
                                        )}
                                    </div>
                                    <span className="text-xs sm:text-sm font-bold text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors line-clamp-1">
                                        {category.name}
                                    </span>
                                </a>
                            ))}
                        </div>
                    </section>
                )}

                {/* 3. Featured Products */}
                <section className="space-y-6">
                    <div className="flex justify-between items-end">
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="p-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg">
                                    <HiStar className="w-5 h-5" />
                                </span>
                                <h2 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                                    Productos Destacados
                                </h2>
                            </div>
                            <p className="text-sm text-gray-500 mt-1">
                                Los favoritos de nuestros clientes con la más alta calificación
                            </p>
                        </div>
                        <a
                            href="/catalog"
                            className="text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline hidden sm:flex items-center gap-1"
                        >
                            Ver más <HiArrowRight className="w-4 h-4" />
                        </a>
                    </div>

                    {featured_products.length > 0 ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            {featured_products.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                    ) : (
                        <div className="p-12 text-center bg-white dark:bg-gray-900 rounded-2xl border border-dashed dark:border-gray-800">
                            <HiOutlineShoppingBag className="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                            <p className="text-sm text-gray-500">Pronto agregaremos productos destacados.</p>
                            <Button
                                color="blue"
                                size="sm"
                                className="mt-4 mx-auto"
                                onClick={() => (window.location.href = '/catalog')}
                            >
                                Explorar Catálogo
                            </Button>
                        </div>
                    )}
                </section>

                {/* 4. Promotional Highlight Banner */}
                <section className="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl p-6 sm:p-10 text-white shadow-lg flex flex-col md:flex-row items-center justify-between gap-6">
                    <div className="space-y-2 text-center md:text-left">
                        <div className="inline-flex items-center gap-1.5 bg-white/20 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                            <HiTag className="w-4 h-4" />
                            <span>Descuentos y Promociones</span>
                        </div>
                        <h3 className="text-2xl sm:text-3xl font-black">
                            ¡Aprovecha nuestros Cupones de Descuento!
                        </h3>
                        <p className="text-sm text-blue-100 max-w-xl">
                            Aplica tus códigos promocionales en el carrito de compras y obtén rebajas exclusivas en tu pedido.
                        </p>
                    </div>
                    <Button
                        color="light"
                        size="lg"
                        onClick={() => (window.location.href = '/catalog')}
                    >
                        <span className="font-bold text-blue-700">Comprar Ahora</span>
                    </Button>
                </section>

                {/* 5. New Arrivals */}
                <section className="space-y-6">
                    <div className="flex justify-between items-end">
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="p-1.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg">
                                    <HiFire className="w-5 h-5" />
                                </span>
                                <h2 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                                    Novedades y Recientes
                                </h2>
                            </div>
                            <p className="text-sm text-gray-500 mt-1">
                                Últimos artículos agregados a nuestro inventario
                            </p>
                        </div>
                        <a
                            href="/catalog"
                            className="text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline hidden sm:flex items-center gap-1"
                        >
                            Ver catálogo <HiArrowRight className="w-4 h-4" />
                        </a>
                    </div>

                    {new_products.length > 0 ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            {new_products.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                    ) : (
                        <div className="p-12 text-center bg-white dark:bg-gray-900 rounded-2xl border border-dashed dark:border-gray-800">
                            <p className="text-sm text-gray-500">Catálogo en constante actualización.</p>
                        </div>
                    )}
                </section>
            </div>
        </StorefrontLayout>
    );
}
