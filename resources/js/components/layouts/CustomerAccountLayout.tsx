import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import CentralLayout from './CentralLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import {
    HiOutlineHome,
    HiOutlineShoppingBag,
    HiOutlineArrowPathRoundedSquare,
    HiOutlineDocumentText,
    HiOutlineHeart,
    HiOutlineTicket,
    HiOutlineStar,
    HiOutlineMapPin,
    HiOutlineUserCircle,
    HiOutlineArrowLeftOnRectangle,
    HiOutlineShieldCheck,
    HiOutlineSparkles,
} from 'react-icons/hi2';

interface CustomerAccountLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export const CustomerAccountLayout: React.FC<CustomerAccountLayoutProps> = ({
    children,
    title = 'Mi Cuenta',
    description = 'Gestiona tus compras, envíos, facturas y configuración personal.',
}) => {
    const { url } = usePage();
    const { customer, isAuthenticated, loading, logout, openAuthModal } = useCustomerAuth();

    const navigationItems = [
        {
            name: 'Resumen',
            href: '/account/dashboard',
            icon: HiOutlineHome,
            active: url === '/account/dashboard' || url === '/account',
        },
        {
            name: 'Mis Pedidos & Tracking',
            href: '/account/orders',
            icon: HiOutlineShoppingBag,
            active: url.startsWith('/account/orders'),
        },
        {
            name: 'Devoluciones & Garantías',
            href: '/account/returns',
            icon: HiOutlineArrowPathRoundedSquare,
            active: url.startsWith('/account/returns'),
        },
        {
            name: 'Mis Facturas PDF',
            href: '/account/invoices',
            icon: HiOutlineDocumentText,
            active: url.startsWith('/account/invoices'),
        },
        {
            name: 'Mis Favoritos',
            href: '/account/wishlist',
            icon: HiOutlineHeart,
            active: url.startsWith('/account/wishlist'),
        },
        {
            name: 'Mis Cupones & Ofertas',
            href: '/account/coupons',
            icon: HiOutlineTicket,
            active: url.startsWith('/account/coupons'),
        },
        {
            name: 'Mis Reseñas',
            href: '/account/reviews',
            icon: HiOutlineStar,
            active: url.startsWith('/account/reviews'),
        },
        {
            name: 'Libreta de Direcciones',
            href: '/account/addresses',
            icon: HiOutlineMapPin,
            active: url.startsWith('/account/addresses'),
        },
        {
            name: 'Soporte & Reporte de Errores',
            href: '/account/support',
            icon: HiOutlineSparkles,
            active: url.startsWith('/account/support'),
        },
        {
            name: 'Mi Perfil & Seguridad',
            href: '/account/profile',
            icon: HiOutlineUserCircle,
            active: url.startsWith('/account/profile'),
        },
    ];

    // Hallazgo G15: esto ignoraba `loading`, asi que mientras se resolvia la sesion se
    // mostraba «Inicia sesion» a alguien que SI la tenia, en cada carga de pagina.
    if (loading) {
        return (
            <CentralLayout>
                <div className="max-w-4xl mx-auto px-4 py-16 text-center text-sm text-gray-500">
                    Cargando tu cuenta…
                </div>
            </CentralLayout>
        );
    }

    if (!isAuthenticated) {
        return (
            <CentralLayout>
                <div className="max-w-4xl mx-auto px-4 py-16 text-center">
                    <div className="w-20 h-20 mx-auto rounded-3xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 flex items-center justify-center mb-6 shadow-inner">
                        <HiOutlineShieldCheck className="w-10 h-10" />
                    </div>
                    <h2 className="text-2xl font-black text-gray-900 dark:text-white mb-2">
                        Inicia sesión con tu OwO Pass
                    </h2>
                    <p className="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-8">
                        Para acceder a tu panel de control, historial de pedidos, facturas y tracking de compras, debes autenticarte.
                    </p>
                    <button
                        onClick={() => openAuthModal('login')}
                        className="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-bold rounded-2xl shadow-lg shadow-blue-500/25 hover:scale-[1.02] transition"
                    >
                        Ingresar con OwO Pass
                    </button>
                </div>
            </CentralLayout>
        );
    }

    return (
        <CentralLayout>
            <div className="bg-gray-50/60 dark:bg-gray-950/60 min-h-[calc(100vh-4rem)] py-8 transition-colors">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header Banner */}
                    <div className="mb-8 p-6 rounded-3xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white shadow-xl shadow-blue-500/10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div className="flex items-center gap-4">
                            <div className="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md text-white font-black text-2xl flex items-center justify-center border border-white/30 shadow-inner">
                                {customer?.avatar ? (
                                    <img src={customer.avatar} alt={customer.name} className="w-full h-full object-cover rounded-2xl" />
                                ) : (
                                    customer?.name ? customer.name.charAt(0).toUpperCase() : 'U'
                                )}
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-black tracking-tight">{title}</h1>
                                    <span className="inline-flex items-center gap-1 bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full text-[11px] font-bold">
                                        <HiOutlineSparkles className="w-3 h-3 text-yellow-300" /> OwO Pass Activo
                                    </span>
                                </div>
                                <p className="text-sm text-blue-100 mt-0.5">
                                    {customer?.name} ({customer?.email})
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <Link
                                href="/marketplace"
                                className="px-4 py-2 bg-white/15 hover:bg-white/25 text-white text-xs font-bold rounded-xl backdrop-blur-sm border border-white/20 transition"
                            >
                                Ir al Catálogo
                            </Link>
                            <button
                                onClick={logout}
                                className="px-4 py-2 bg-red-500/80 hover:bg-red-600 text-white text-xs font-bold rounded-xl backdrop-blur-sm transition flex items-center gap-1.5"
                            >
                                <HiOutlineArrowLeftOnRectangle className="w-4 h-4" />
                                Cerrar Sesión
                            </button>
                        </div>
                    </div>

                    {/* Main Layout: Sidebar & Content Area */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        {/* Sidebar Navigation */}
                        <aside className="lg:col-span-3">
                            <div className="bg-white dark:bg-gray-900 rounded-3xl p-3 shadow-sm border border-gray-200/80 dark:border-gray-800/80 sticky top-24">
                                <div className="px-3 py-2 text-[11px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                    Menú Principal
                                </div>
                                <nav className="space-y-1 mt-1">
                                    {navigationItems.map((item) => {
                                        const Icon = item.icon;
                                        return (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                className={`flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-xs font-bold transition ${
                                                    item.active
                                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                                }`}
                                            >
                                                <Icon className={`w-4 h-4 ${item.active ? 'text-white' : 'text-blue-600 dark:text-blue-400'}`} />
                                                <span className="flex-1">{item.name}</span>
                                            </Link>
                                        );
                                    })}
                                </nav>
                            </div>
                        </aside>

                        {/* Main Content Area */}
                        <main className="lg:col-span-9">
                            {children}
                        </main>
                    </div>
                </div>
            </div>
        </CentralLayout>
    );
};

export default CustomerAccountLayout;
