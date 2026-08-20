import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CentralLayout from '@/components/layouts/CentralLayout';
import {
    HiOutlineBuildingStorefront,
    HiOutlineSparkles,
    HiOutlineRocketLaunch,
    HiOutlineShieldCheck,
    HiOutlineCurrencyDollar,
    HiOutlineDevicePhoneMobile,
    HiOutlineDocumentText,
    HiOutlineArrowRight,
    HiOutlineCheckCircle,
    HiOutlineGlobeAlt,
    HiOutlineChartBar,
    HiOutlineTag,
    HiOutlineTruck,
    HiOutlineQuestionMarkCircle,
    HiOutlineChevronDown,
    HiOutlineCheck,
} from 'react-icons/hi2';

interface MerchantLandingPageProps {
    domain?: string;
    featured_stores?: Array<{
        id: string;
        name: string;
        slug: string;
        domain: string;
        logo: string | null;
        products_count: number;
    }>;
    plans?: Array<{
        id: string;
        name: string;
        slug: string;
        price: number;
        billing_interval: string;
        commission_rate: number;
        max_products: number;
        features: string[];
    }>;
    total_stores_count?: number;
    total_products_count?: number;
}

const FAQS = [
    {
        q: '¿Cómo cobro las ventas de mi tienda?',
        a: 'OwOMarket liquida automáticamente tus ventas a través de Pago Móvil en Bolívares (usando la tasa oficial del BCV del día) o en USDT mediante Binance Pay. Los fondos van directamente a tu cuenta bancaria o wallet registrada.',
    },
    {
        q: '¿Qué es el modelo de Doble Canal de OwOMarket?',
        a: 'Al registrarte obtienes de inmediato tu propia tienda online con subdominio exclusivo (ejemplo: tu-marca.owomarket.local) y además puedes publicar tus productos en el Marketplace Central de OwOMarket para que miles de compradores te descubran.',
    },
    {
        q: '¿Necesito conocimientos técnicos para abrir mi tienda?',
        a: 'Para nada. La plataforma está 100% lista para usar. En menos de 2 minutos creas tu tienda, cargas tus productos con fotos y precios, y ya puedes empezar a recibir pedidos con carrito y checkout integrado.',
    },
    {
        q: '¿Puedo personalizar el diseño y las políticas de mi tienda?',
        a: 'Sí, desde tu panel de control puedes configurar el logo, banners promocionales, colores de marca, zonas de despacho con tarifas personalizadas, impuestos y emitir facturas digitales oficiales en PDF.',
    },
    {
        q: '¿Cuánto cuesta empezar y cómo funcionan las comisiones?',
        a: 'Puedes empezar completamente gratis con nuestro Plan Inicial sin cuota mensual. Solo se cobra una pequeña comisión transparente por venta efectiva realizada a través del Marketplace Central.',
    },
];

const MerchantLandingPageContent: React.FC<MerchantLandingPageProps> = ({
    featured_stores = [],
    plans = [],
    total_stores_count = 9,
    total_products_count = 150,
}) => {
    // Interactive Calculator State
    const [monthlySales, setMonthlySales] = useState<number>(1500);
    const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(0);

    const toggleFaq = (index: number) => {
        setOpenFaqIndex(openFaqIndex === index ? null : index);
    };

    // Calculation estimates
    const standardCommission = 0.05; // 5%
    const estimatedPlatformFee = monthlySales * standardCommission;
    const estimatedTakeHome = monthlySales - estimatedPlatformFee;

    return (
        <>
            <Head title="Vende con Nosotros - Abre tu Tienda Online en OwOMarket" />

            <div className="space-y-16 sm:space-y-24 py-4 sm:py-8">
                {/* 1. HERO SECTION */}
                <section className="relative rounded-3xl overflow-hidden bg-gradient-to-br from-gray-900 via-blue-950 to-indigo-950 text-white p-8 sm:p-14 lg:p-20 shadow-2xl border border-blue-900/40">
                    {/* Glowing orbs */}
                    <div className="absolute top-0 right-0 -mt-16 -mr-16 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl pointer-events-none" />
                    <div className="absolute bottom-0 left-0 -mb-16 -ml-16 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none" />

                    <div className="relative z-10 max-w-3xl space-y-6 sm:space-y-8">
                        <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                            <HiOutlineRocketLaunch className="w-4 h-4 text-amber-400 animate-pulse" />
                            <span>Impulsa tu negocio digital en Venezuela</span>
                        </div>

                        <h1 className="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight">
                            Tu Propia Tienda Online.{' '}
                            <span className="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 via-indigo-300 to-purple-400">
                                Todo el Poder del Marketplace.
                            </span>
                        </h1>

                        <p className="text-sm sm:text-lg text-gray-300 leading-relaxed max-w-2xl font-medium">
                            Abre tu sucursal digital en 2 minutos con subdominio propio, catálogo con variantes, facturación fiscal en PDF y pasarelas de Pago Móvil BCV y Binance Pay listas para cobrar.
                        </p>

                        <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 pt-2">
                            <Link
                                href="/tenant/create/account"
                                className="px-8 py-4 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 hover:from-blue-500 hover:to-indigo-600 text-white font-black text-sm sm:text-base shadow-xl shadow-blue-500/30 hover:scale-[1.02] active:scale-[0.98] transition flex items-center justify-center gap-2 text-center"
                            >
                                <HiOutlineBuildingStorefront className="w-5 h-5" />
                                <span>Abrir mi Tienda Gratis</span>
                                <HiOutlineArrowRight className="w-4 h-4" />
                            </Link>

                            <a
                                href="#planes"
                                className="px-6 py-4 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm border border-white/20 backdrop-blur-md transition flex items-center justify-center text-center"
                            >
                                Ver Planes y Comisiones
                            </a>
                        </div>

                        {/* Quick Trust Badges */}
                        <div className="pt-6 border-t border-white/10 grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs text-gray-300 font-semibold">
                            <div className="flex items-center gap-2">
                                <HiOutlineCheckCircle className="w-5 h-5 text-emerald-400 shrink-0" />
                                <span>Sin contratos forzosos</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <HiOutlineCheckCircle className="w-5 h-5 text-emerald-400 shrink-0" />
                                <span>Tasa oficial BCV en tiempo real</span>
                            </div>
                            <div className="flex items-center gap-2 col-span-2 sm:col-span-1">
                                <HiOutlineCheckCircle className="w-5 h-5 text-emerald-400 shrink-0" />
                                <span>Facturas automáticas en PDF</span>
                            </div>
                        </div>
                    </div>
                </section>

                {/* 2. EL PODER DEL DOBLE CANAL */}
                <section className="space-y-8">
                    <div className="text-center max-w-2xl mx-auto space-y-3">
                        <span className="text-xs font-black tracking-wider uppercase text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/40 px-3 py-1 rounded-full">
                            Arquitectura Doble Canal
                        </span>
                        <h2 className="text-2xl sm:text-4xl font-black text-gray-900 dark:text-white">
                            ¿Por qué elegir OwOMarket?
                        </h2>
                        <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                            No tienes que elegir entre tener tu propia página web o vender en un marketplace. En OwOMarket obtienes ambas soluciones sincronizadas en una sola plataforma.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                        {/* Canal 1: Tienda Propia */}
                        <div className="p-8 rounded-3xl bg-white dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 shadow-sm space-y-5 hover:shadow-lg transition">
                            <div className="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <HiOutlineGlobeAlt className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-black text-gray-900 dark:text-white">
                                1. Tu Tienda Privada Exclusiva
                            </h3>
                            <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                Tu propia dirección web con subdominio (ej: <code className="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-blue-600 dark:text-blue-300">mitienda.owomarket.local</code>) donde tus clientes compran únicamente tus productos con tu identidad de marca, carrito privado y sin distracciones de la competencia.
                            </p>
                            <ul className="space-y-2 text-xs text-gray-600 dark:text-gray-300 font-medium">
                                <li className="flex items-center gap-2">
                                    <HiOutlineCheck className="w-4 h-4 text-emerald-500" />
                                    Subdominio dedicado y personalizable
                                </li>
                                <li className="flex items-center gap-2">
                                    <HiOutlineCheck className="w-4 h-4 text-emerald-500" />
                                    Control total de logo, banner y redes sociales
                                </li>
                                <li className="flex items-center gap-2">
                                    <HiOutlineCheck className="w-4 h-4 text-emerald-500" />
                                    Checkout directo a tus cuentas bancarias
                                </li>
                            </ul>
                        </div>

                        {/* Canal 2: Marketplace Central */}
                        <div className="p-8 rounded-3xl bg-white dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 shadow-sm space-y-5 hover:shadow-lg transition">
                            <div className="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <HiOutlineBuildingStorefront className="w-6 h-6" />
                            </div>
                            <h3 className="text-xl font-black text-gray-900 dark:text-white">
                                2. Exposición en el Marketplace Central
                            </h3>
                            <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                Con un solo clic puedes publicar los productos que desees en el catálogo general de OwOMarket. Tus artículos aparecerán en el buscador global, carrusel de tiendas y serán elegibles para el carrito multi-tienda.
                            </p>
                            <ul className="space-y-2 text-xs text-gray-600 dark:text-gray-300 font-medium">
                                <li className="flex items-center gap-2">
                                    <HiOutlineCheck className="w-4 h-4 text-purple-500" />
                                    Publicación selectiva de productos
                                </li>
                                <li className="flex items-center gap-2">
                                    <HiOutlineCheck className="w-4 h-4 text-purple-500" />
                                    Clientes con cuenta única universal OwO Pass SSO
                                </li>
                                <li className="flex items-center gap-2">
                                    <HiOutlineCheck className="w-4 h-4 text-purple-500" />
                                    Insignia de vendedor oficial verificado
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                {/* 3. SUITE DE HERRAMIENTAS DEL BACKOFFICE */}
                <section className="space-y-8">
                    <div className="text-center max-w-2xl mx-auto space-y-3">
                        <span className="text-xs font-black tracking-wider uppercase text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/40 px-3 py-1 rounded-full">
                            Panel de Control Todo en Uno
                        </span>
                        <h2 className="text-2xl sm:text-4xl font-black text-gray-900 dark:text-white">
                            Todas las herramientas que tu negocio necesita
                        </h2>
                        <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            Diseñado específicamente para el comercio venezolano, facilitando ventas en múltiples monedas y trámites contables.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div className="p-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3">
                            <div className="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <HiOutlineDevicePhoneMobile className="w-5 h-5" />
                            </div>
                            <h4 className="font-bold text-sm text-gray-900 dark:text-white">
                                Pago Móvil & Binance Pay
                            </h4>
                            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                Cobro directo en Bolívares con tasa BCV automática o en USDT sin intermediarios bancarios complejos.
                            </p>
                        </div>

                        <div className="p-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3">
                            <div className="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 flex items-center justify-center">
                                <HiOutlineDocumentText className="w-5 h-5" />
                            </div>
                            <h4 className="font-bold text-sm text-gray-900 dark:text-white">
                                Facturación Digital en PDF
                            </h4>
                            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                Emite comprobantes fiscales y facturas digitales con montos en USD y Bolívares listas para descargar o imprimir.
                            </p>
                        </div>

                        <div className="p-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3">
                            <div className="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <HiOutlineTag className="w-5 h-5" />
                            </div>
                            <h4 className="font-bold text-sm text-gray-900 dark:text-white">
                                Catálogo con Variantes & Stock
                            </h4>
                            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                Administra tallas, colores, especificaciones, múltiples fotos y control de existencias en tiempo real.
                            </p>
                        </div>

                        <div className="p-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3">
                            <div className="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <HiOutlineTruck className="w-5 h-5" />
                            </div>
                            <h4 className="font-bold text-sm text-gray-900 dark:text-white">
                                Envíos por Zonas y Tarifas
                            </h4>
                            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                Define delivery local, envíos nacionales por MRW/Zoom/Tealca y costos de despacho por estado o ciudad.
                            </p>
                        </div>

                        <div className="p-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3">
                            <div className="w-10 h-10 rounded-xl bg-pink-100 dark:bg-pink-900/40 text-pink-600 dark:text-pink-400 flex items-center justify-center">
                                <HiOutlineSparkles className="w-5 h-5" />
                            </div>
                            <h4 className="font-bold text-sm text-gray-900 dark:text-white">
                                Cupones y Promociones
                            </h4>
                            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                Crea descuentos por porcentaje, monto fijo, fechas de vigencia y límites de uso para fidelizar a tus clientes.
                            </p>
                        </div>

                        <div className="p-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-3">
                            <div className="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <HiOutlineChartBar className="w-5 h-5" />
                            </div>
                            <h4 className="font-bold text-sm text-gray-900 dark:text-white">
                                Reseñas y Reputación
                            </h4>
                            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                Recibe calificaciones de compradores verificados, modera opiniones y responde directamente a tus clientes.
                            </p>
                        </div>
                    </div>
                </section>

                {/* 4. TABLA DE PLANES Y PRECIOS */}
                <section id="planes" className="space-y-8 scroll-mt-20">
                    <div className="text-center max-w-2xl mx-auto space-y-3">
                        <span className="text-xs font-black tracking-wider uppercase text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/40 px-3 py-1 rounded-full">
                            Tarifas Transparentes
                        </span>
                        <h2 className="text-2xl sm:text-4xl font-black text-gray-900 dark:text-white">
                            Planes diseñados para cada etapa de tu negocio
                        </h2>
                        <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            Empieza gratis y escala a medida que aumenten tus ventas. Sin costos ocultos.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                        {plans.map((plan, idx) => {
                            const isPopular = plan.slug === 'pro' || idx === 1;

                            return (
                                <div
                                    key={plan.id}
                                    className={`relative rounded-3xl p-8 transition flex flex-col justify-between ${
                                        isPopular
                                            ? 'bg-gradient-to-b from-blue-900/20 to-indigo-950/40 border-2 border-blue-500 dark:border-blue-400 shadow-xl shadow-blue-500/10'
                                            : 'bg-white dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 shadow-sm'
                                    }`}
                                >
                                    {isPopular && (
                                        <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-black text-[10px] uppercase tracking-wider shadow-md">
                                            Recomendado para Comercios
                                        </div>
                                    )}

                                    <div className="space-y-6">
                                        <div>
                                            <h3 className="text-lg font-black text-gray-900 dark:text-white">
                                                {plan.name}
                                            </h3>
                                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Comisión: <strong className="text-blue-600 dark:text-blue-400">{plan.commission_rate}%</strong> por venta
                                            </p>
                                        </div>

                                        <div className="flex items-baseline gap-1">
                                            <span className="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white">
                                                ${plan.price.toFixed(2)}
                                            </span>
                                            <span className="text-xs text-gray-500 dark:text-gray-400 font-semibold">
                                                USD / {plan.billing_interval === 'monthly' ? 'mes' : plan.billing_interval}
                                            </span>
                                        </div>

                                        <div className="space-y-3 pt-4 border-t border-gray-100 dark:border-gray-700 text-xs">
                                            <div className="font-bold text-gray-700 dark:text-gray-200">Incluye:</div>
                                            <ul className="space-y-2.5">
                                                {plan.features && plan.features.length > 0 ? (
                                                    plan.features.map((feat, fIdx) => (
                                                        <li key={fIdx} className="flex items-start gap-2 text-gray-600 dark:text-gray-300">
                                                            <HiOutlineCheck className="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                                                            <span>{feat}</span>
                                                        </li>
                                                    ))
                                                ) : (
                                                    <>
                                                        <li className="flex items-start gap-2 text-gray-600 dark:text-gray-300">
                                                            <HiOutlineCheck className="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                                                            <span>Hasta {plan.max_products} productos</span>
                                                        </li>
                                                        <li className="flex items-start gap-2 text-gray-600 dark:text-gray-300">
                                                            <HiOutlineCheck className="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                                                            <span>Tienda online con subdominio propio</span>
                                                        </li>
                                                        <li className="flex items-start gap-2 text-gray-600 dark:text-gray-300">
                                                            <HiOutlineCheck className="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                                                            <span>Presencia en Marketplace Central</span>
                                                        </li>
                                                    </>
                                                )}
                                            </ul>
                                        </div>
                                    </div>

                                    <div className="pt-8">
                                        <Link
                                            href="/tenant/create/account"
                                            className={`w-full py-3.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition ${
                                                isPopular
                                                    ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20'
                                                    : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-white'
                                            }`}
                                        >
                                            <span>Comenzar con {plan.name.split('/')[0]}</span>
                                            <HiOutlineArrowRight className="w-3.5 h-3.5" />
                                        </Link>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {/* 5. CALCULADORA INTERACTIVA DE GANANCIAS */}
                <section className="p-8 sm:p-12 rounded-3xl bg-gradient-to-br from-blue-50 via-indigo-50/40 to-purple-50 dark:from-gray-800 dark:via-gray-850 dark:to-gray-900 border border-blue-200/60 dark:border-gray-700 space-y-8 shadow-sm">
                    <div className="max-w-2xl mx-auto text-center space-y-2">
                        <h3 className="text-xl sm:text-3xl font-black text-gray-900 dark:text-white flex items-center justify-center gap-2">
                            <HiOutlineCurrencyDollar className="w-7 h-7 text-emerald-600" />
                            Calcula tus ingresos con OwOMarket
                        </h3>
                        <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            Ajusta el volumen estimado de ventas mensuales y visualiza cuánto recibes neto en tu cuenta.
                        </p>
                    </div>

                    <div className="max-w-xl mx-auto space-y-6">
                        <div className="space-y-2">
                            <div className="flex justify-between items-center text-xs font-bold text-gray-700 dark:text-gray-200">
                                <span>Ventas estimadas por mes:</span>
                                <span className="text-base text-blue-600 dark:text-blue-400 font-black">
                                    ${monthlySales.toLocaleString()} USD
                                </span>
                            </div>
                            <input
                                type="range"
                                min="200"
                                max="10000"
                                step="100"
                                value={monthlySales}
                                onChange={e => setMonthlySales(Number(e.target.value))}
                                className="w-full h-2.5 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-blue-600"
                            />
                            <div className="flex justify-between text-[11px] text-gray-400 font-medium">
                                <span>$200 USD</span>
                                <span>$5,000 USD</span>
                                <span>$10,000 USD</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-center">
                            <div className="p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                <span className="text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                                    Comisión de Plataforma (5%)
                                </span>
                                <div className="text-lg font-black text-gray-700 dark:text-gray-300 mt-1">
                                    ${estimatedPlatformFee.toFixed(2)} USD
                                </div>
                            </div>

                            <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                                <span className="text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                                    Tus Ingresos Netos
                                </span>
                                <div className="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                                    ${estimatedTakeHome.toFixed(2)} USD
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* 6. SHOWCASE DE TIENDAS OFICIALES */}
                {featured_stores && featured_stores.length > 0 && (
                    <section className="space-y-6">
                        <div className="text-center max-w-xl mx-auto space-y-2">
                            <h3 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">
                                Tiendas que ya confían en OwOMarket
                            </h3>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                Más de {total_stores_count} comercios y {total_products_count}+ productos activos en la plataforma.
                            </p>
                        </div>

                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            {featured_stores.map(store => (
                                <a
                                    key={store.id}
                                    href={`http://${store.domain}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="p-5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-blue-500 hover:shadow-md transition text-center space-y-3 group"
                                >
                                    <div className="w-14 h-14 mx-auto rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center font-black text-blue-600 dark:text-blue-400 text-lg group-hover:scale-105 transition">
                                        {store.logo ? (
                                            <img src={store.logo} alt={store.name} className="w-full h-full object-cover rounded-2xl" />
                                        ) : (
                                            store.name.substring(0, 2).toUpperCase()
                                        )}
                                    </div>
                                    <div>
                                        <h4 className="font-bold text-xs text-gray-900 dark:text-white truncate">
                                            {store.name}
                                        </h4>
                                        <span className="text-[10px] text-gray-400">
                                            {store.products_count} productos
                                        </span>
                                    </div>
                                </a>
                            ))}
                        </div>
                    </section>
                )}

                {/* 7. PREGUNTAS FRECUENTES (FAQ) */}
                <section className="space-y-8 max-w-3xl mx-auto">
                    <div className="text-center space-y-2">
                        <span className="text-xs font-black tracking-wider uppercase text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/40 px-3 py-1 rounded-full">
                            Resolvemos tus dudas
                        </span>
                        <h2 className="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white">
                            Preguntas Frecuentes de Comercios
                        </h2>
                    </div>

                    <div className="space-y-3">
                        {FAQS.map((faq, index) => {
                            const isOpen = openFaqIndex === index;

                            return (
                                <div
                                    key={index}
                                    className="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800/80 overflow-hidden transition"
                                >
                                    <button
                                        onClick={() => toggleFaq(index)}
                                        className="w-full p-5 text-left font-bold text-xs sm:text-sm text-gray-900 dark:text-white flex items-center justify-between gap-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition"
                                    >
                                        <span className="flex items-center gap-2">
                                            <HiOutlineQuestionMarkCircle className="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0" />
                                            {faq.q}
                                        </span>
                                        <HiOutlineChevronDown
                                            className={`w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0 ${
                                                isOpen ? 'rotate-180 text-blue-600' : ''
                                            }`}
                                        />
                                    </button>

                                    {isOpen && (
                                        <div className="px-5 pb-5 pt-1 text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed border-t border-gray-100 dark:border-gray-700/60">
                                            {faq.a}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </section>

                {/* 8. CTA FINAL */}
                <section className="p-8 sm:p-14 rounded-3xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-700 text-white text-center space-y-6 shadow-2xl shadow-blue-500/20">
                    <div className="max-w-2xl mx-auto space-y-3">
                        <h2 className="text-2xl sm:text-4xl font-black">
                            ¿Listo para empezar a vender hoy mismo?
                        </h2>
                        <p className="text-xs sm:text-sm text-blue-100 leading-relaxed font-medium">
                            Crea tu cuenta de comercio gratis en menos de 2 minutos. No requieres tarjeta de crédito ni conocimientos técnicos.
                        </p>
                    </div>

                    <div className="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                        <Link
                            href="/tenant/create/account"
                            className="w-full sm:w-auto px-8 py-4 bg-white hover:bg-gray-50 text-blue-700 font-black text-sm rounded-2xl shadow-xl hover:scale-[1.02] active:scale-[0.98] transition flex items-center justify-center gap-2"
                        >
                            <HiOutlineBuildingStorefront className="w-5 h-5" />
                            <span>Crear Mi Tienda Ahora</span>
                            <HiOutlineArrowRight className="w-4 h-4" />
                        </Link>
                    </div>
                </section>
            </div>
        </>
    );
};

export const MerchantLandingPage: React.FC<MerchantLandingPageProps> = (props) => {
    return (
        <CentralLayout>
            <MerchantLandingPageContent {...props} />
        </CentralLayout>
    );
};

export default MerchantLandingPage;
