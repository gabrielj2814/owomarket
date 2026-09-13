import NotificationBell from '@/components/ui/NotificationBell';
import CustomerAuthModal from '@/components/ui/storefront/CustomerAuthModal';
import { CentralCartProvider, useCentralCart } from '@/contexts/CentralCartContext';
import { CustomerAuthProvider, useCustomerAuth } from '@/contexts/CustomerAuthContext';
import storefrontTheme from '@/theme/storefrontTheme';
import { Link } from '@inertiajs/react';
import { Dropdown, DropdownDivider, DropdownHeader, DropdownItem, ThemeProvider } from 'flowbite-react';
import React, { FC, useState } from 'react';
import {
    HiOutlineBuildingStorefront,
    HiOutlineCurrencyDollar,
    HiOutlineDevicePhoneMobile,
    HiOutlineMagnifyingGlass,
    HiOutlineShieldCheck,
    HiOutlineShoppingBag,
    HiOutlineSparkles,
    HiOutlineUser,
} from 'react-icons/hi2';
import CentralCartDrawer from '../ui/marketplace/CentralCartDrawer';

interface CentralLayoutProps {
    children?: React.ReactNode;
}

const CentralNavbar: React.FC = () => {
    const { getItemCount, setIsDrawerOpen } = useCentralCart();
    const { customer, isAuthenticated, logout, openAuthModal } = useCustomerAuth();
    const [searchQuery, setSearchQuery] = useState('');
    const itemCount = getItemCount();

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            window.location.href = `/marketplace?search=${encodeURIComponent(searchQuery.trim())}`;
        }
    };

    return (
        <header className="sticky top-0 z-40 border-b border-gray-200/80 bg-white/95 backdrop-blur-md transition-colors dark:border-gray-800/80 dark:bg-gray-900/95">
            {/* Top Bar Announcement */}
            <div className="flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 px-4 py-1.5 text-center text-xs font-medium text-white">
                <span className="inline-flex items-center gap-1 rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold">
                    <HiOutlineSparkles className="h-3 w-3" /> NUEVO
                </span>
                <span>
                    Marketplace Multi-Tienda Central de OwOMarket. Compra en varias tiendas y paga en una sola factura con Pago Móvil o Binance Pay.
                </span>
            </div>

            {/* Main Navigation */}
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="flex h-16 items-center justify-between gap-4">
                    {/* Brand Logo */}
                    <Link href="/" className="group flex flex-shrink-0 items-center gap-2.5">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-xl font-black text-white shadow-md shadow-blue-500/20 transition group-hover:scale-105">
                            OwO
                        </div>
                        <div>
                            <span className="flex items-center gap-1.5 text-lg font-extrabold tracking-tight text-gray-900 dark:text-white">
                                OwOMarket
                                <span className="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-blue-700 uppercase dark:bg-blue-900/50 dark:text-blue-300">
                                    Central
                                </span>
                            </span>
                        </div>
                    </Link>

                    {/* Global Search Bar */}
                    <form onSubmit={handleSearch} className="hidden max-w-xl flex-1 items-center md:flex">
                        <div className="relative w-full">
                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <HiOutlineMagnifyingGlass className="h-4 w-4" />
                            </div>
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Buscar productos, marcas o tiendas oficiales..."
                                className="w-full rounded-xl border-0 bg-gray-100/80 py-2 pr-24 pl-10 text-sm text-gray-900 placeholder-gray-400 transition focus:ring-2 focus:ring-blue-500 dark:bg-gray-800/80 dark:text-white"
                            />
                            <button
                                type="submit"
                                className="absolute top-1.5 right-1.5 bottom-1.5 rounded-lg bg-blue-600 px-3 text-xs font-semibold text-white transition hover:bg-blue-700"
                            >
                                Buscar
                            </button>
                        </div>
                    </form>

                    {/* Navigation Links & Actions */}
                    <div className="flex items-center gap-2 sm:gap-3">
                        <Link
                            href="/marketplace"
                            className="hidden items-center gap-1 rounded-lg px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 hover:text-blue-600 lg:flex dark:text-gray-200 dark:hover:bg-gray-800 dark:hover:text-blue-400"
                        >
                            Explorar Catálogo
                        </Link>

                        <Link
                            href="/vender"
                            className="hidden items-center gap-1 rounded-xl bg-blue-50 px-3 py-2 text-xs font-bold text-blue-600 transition hover:bg-blue-100 sm:flex dark:bg-blue-950/60 dark:text-blue-400 dark:hover:bg-blue-900/60"
                        >
                            <HiOutlineBuildingStorefront className="h-4 w-4" />
                            <span>Vende con Nosotros</span>
                        </Link>

                        {/*
                         * La campana solo con sesion: sin ella no hay buzon que pedir, y una
                         * campana que siempre sale vacia ensena a ignorarla.
                         */}
                        {isAuthenticated && customer && <NotificationBell audience="customer" />}

                        {/* Customer Auth / OwO Pass SSO */}
                        {isAuthenticated && customer ? (
                            <Dropdown
                                label=""
                                dismissOnClick={true}
                                renderTrigger={() => (
                                    <button className="flex items-center gap-2 rounded-xl bg-blue-50 p-1.5 font-semibold text-blue-700 transition-colors hover:bg-blue-100 sm:p-2 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50">
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white shadow-sm">
                                            {(customer.name || 'U')[0].toUpperCase()}
                                        </div>
                                        <span className="hidden max-w-[110px] truncate text-xs lg:inline">{customer.name}</span>
                                    </button>
                                )}
                            >
                                <DropdownHeader>
                                    <span className="block text-sm font-bold text-gray-900 dark:text-white">{customer.name}</span>
                                    <span className="block truncate text-xs text-gray-500">{customer.email}</span>
                                </DropdownHeader>
                                <DropdownItem href="/account/dashboard">📊 Mi Dashboard</DropdownItem>
                                <DropdownItem href="/account/orders">📦 Mis Pedidos & Tracking</DropdownItem>
                                <DropdownItem href="/account/invoices">🧾 Mis Facturas PDF</DropdownItem>
                                <DropdownItem href="/account/wishlist">❤️ Mis Favoritos</DropdownItem>
                                <DropdownItem href="/account/coupons">🎟️ Mis Cupones</DropdownItem>
                                <DropdownItem href="/account/profile">⚙️ Mi Perfil & Seguridad</DropdownItem>
                                <DropdownDivider />
                                <DropdownItem onClick={logout}>🚪 Cerrar Sesión</DropdownItem>
                            </Dropdown>
                        ) : (
                            <button
                                onClick={() => openAuthModal()}
                                className="inline-flex items-center gap-1.5 rounded-xl bg-gray-100 px-3.5 py-2 text-xs font-bold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                <HiOutlineUser className="h-4 w-4 text-blue-600" />
                                <span className="hidden sm:inline">OwO Pass</span>
                            </button>
                        )}

                        {/* Multi-Store Cart Button */}
                        <button
                            onClick={() => setIsDrawerOpen(true)}
                            className="relative flex items-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700"
                        >
                            <HiOutlineShoppingBag className="h-4 w-4" />
                            <span className="hidden sm:inline">Carrito</span>
                            {itemCount > 0 && (
                                <span className="rounded-full bg-white px-1.5 py-0.5 text-[10px] font-black text-blue-600">{itemCount}</span>
                            )}
                        </button>
                    </div>
                </div>

                {/* Mobile Search Bar */}
                <div className="pb-3 md:hidden">
                    <form onSubmit={handleSearch} className="relative w-full">
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Buscar en todo el marketplace..."
                            className="w-full rounded-xl border-0 bg-gray-100 py-2 pr-16 pl-9 text-xs text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-white"
                        />
                        <button
                            type="submit"
                            className="absolute top-1 right-1 bottom-1 rounded-lg bg-blue-600 px-3 text-[10px] font-bold text-white"
                        >
                            Buscar
                        </button>
                    </form>
                </div>
            </div>
        </header>
    );
};

const CentralFooter: React.FC = () => {
    return (
        <footer className="mt-20 border-t border-gray-800 bg-gray-900 pt-12 pb-8 text-gray-400">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="mb-8 grid grid-cols-1 gap-8 md:grid-cols-4">
                    {/* Brand Info */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 text-sm font-black text-white">
                                OwO
                            </div>
                            <span className="text-base font-extrabold text-white">OwOMarket Central</span>
                        </div>
                        <p className="text-xs leading-relaxed text-gray-400">
                            La plataforma multi-tienda definitiva donde puedes comprar en diferentes tiendas asociadas y pagar en una sola transacción
                            unificada.
                        </p>
                    </div>

                    {/* Quick Links */}
                    <div>
                        <h4 className="mb-3 text-xs font-bold tracking-wider text-white uppercase">Marketplace</h4>
                        <ul className="space-y-2 text-xs">
                            <li>
                                <Link href="/" className="transition hover:text-white">
                                    Inicio
                                </Link>
                            </li>
                            <li>
                                <Link href="/marketplace" className="transition hover:text-white">
                                    Explorar Catálogo
                                </Link>
                            </li>
                            <li>
                                <Link href="/vender" className="font-bold text-blue-400 transition hover:text-blue-300">
                                    Vende con Nosotros (Crear Tienda)
                                </Link>
                            </li>
                            <li>
                                <Link href="/cart" className="transition hover:text-white">
                                    Carrito Multi-Tienda
                                </Link>
                            </li>
                            <li>
                                <Link href="/checkout" className="transition hover:text-white">
                                    Checkout Unificado
                                </Link>
                            </li>
                        </ul>
                    </div>

                    {/* Security & Guarantees */}
                    <div>
                        <h4 className="mb-3 text-xs font-bold tracking-wider text-white uppercase">Garantía y Seguridad</h4>
                        <ul className="space-y-2 text-xs">
                            <li className="flex items-center gap-2">
                                <HiOutlineShieldCheck className="h-4 w-4 text-green-400" /> Compra 100% Protegida
                            </li>
                            <li className="flex items-center gap-2">
                                <HiOutlineSparkles className="h-4 w-4 text-purple-400" /> OwO Pass Universal SSO
                            </li>
                            <li className="flex items-center gap-2">
                                <HiOutlineBuildingStorefront className="h-4 w-4 text-blue-400" /> Tiendas Verificadas
                            </li>
                        </ul>
                    </div>

                    {/* Payment Gateways */}
                    <div>
                        <h4 className="mb-3 text-xs font-bold tracking-wider text-white uppercase">Métodos de Pago</h4>
                        <div className="space-y-2.5">
                            <div className="flex items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/60 p-2">
                                <HiOutlineDevicePhoneMobile className="h-5 w-5 text-blue-400" />
                                <div>
                                    <p className="text-xs font-bold text-white">Pago Móvil</p>
                                    <p className="text-[10px] text-gray-400">Bancos Nacionales de Venezuela (Bs.)</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2 rounded-lg border border-gray-700/60 bg-gray-800/60 p-2">
                                <HiOutlineCurrencyDollar className="h-5 w-5 text-yellow-400" />
                                <div>
                                    <p className="text-xs font-bold text-white">Binance Pay</p>
                                    <p className="text-[10px] text-gray-400">Pagos instantáneos con USDT</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex flex-col items-center justify-between border-t border-gray-800 pt-6 text-xs text-gray-500 sm:flex-row">
                    <p>© {new Date().getFullYear()} OwOMarket. Todos los derechos reservados.</p>
                    <p className="mt-2 sm:mt-0">Diseñado con tecnología multi-inquilino de alto rendimiento.</p>
                </div>
            </div>
        </footer>
    );
};

const CentralLayoutContent: React.FC<CentralLayoutProps> = ({ children }) => {
    return (
        <div className="flex min-h-screen flex-col bg-gray-50 text-gray-900 antialiased selection:bg-blue-500 selection:text-white dark:bg-gray-950 dark:text-gray-100">
            <CentralNavbar />
            <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">{children}</main>
            <CentralFooter />
            <CentralCartDrawer />
            <CustomerAuthModal />
        </div>
    );
};

/*
 * El tema del escaparate se aplica AQUI, en la raiz de todo lo publico.
 *
 * `CustomerAccountLayout` --el portal del cliente-- se construye encima de este layout, asi que
 * heredaria este tema. No lo hace: su `<ThemeProvider>` lleva `root`, que corta la herencia.
 * Son dos superficies distintas y el portal tiene el suyo.
 */
const CentralLayout: FC<CentralLayoutProps> = ({ children }) => {
    return (
        <ThemeProvider theme={storefrontTheme}>
            <CustomerAuthProvider>
                <CentralCartProvider>
                    <CentralLayoutContent>{children}</CentralLayoutContent>
                </CentralCartProvider>
            </CustomerAuthProvider>
        </ThemeProvider>
    );
};

export default CentralLayout;
