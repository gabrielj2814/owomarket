import React, { FC, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { CentralCartProvider, useCentralCart } from '@/contexts/CentralCartContext';
import { CustomerAuthProvider, useCustomerAuth } from '@/contexts/CustomerAuthContext';
import { Dropdown, DropdownDivider, DropdownHeader, DropdownItem } from 'flowbite-react';
import CentralCartDrawer from '../ui/marketplace/CentralCartDrawer';
import CustomerAuthModal from '@/components/ui/storefront/CustomerAuthModal';
import {
    HiOutlineShoppingBag,
    HiOutlineMagnifyingGlass,
    HiOutlineUser,
    HiOutlineBuildingStorefront,
    HiOutlineSparkles,
    HiOutlineShieldCheck,
    HiOutlineDevicePhoneMobile,
    HiOutlineCurrencyDollar,
    HiArrowRightOnRectangle,
} from 'react-icons/hi2';

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
        <header className="sticky top-0 z-40 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-b border-gray-200/80 dark:border-gray-800/80 transition-colors">
            {/* Top Bar Announcement */}
            <div className="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white text-xs py-1.5 px-4 text-center font-medium flex items-center justify-center gap-2">
                <span className="inline-flex items-center gap-1 bg-white/20 px-2 py-0.5 rounded-full text-[10px] font-bold">
                    <HiOutlineSparkles className="w-3 h-3" /> NUEVO
                </span>
                <span>Marketplace Multi-Tienda Central de OwOMarket. Compra en varias tiendas y paga en una sola factura con Pago Móvil o Binance Pay.</span>
            </div>

            {/* Main Navigation */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between h-16 gap-4">
                    {/* Brand Logo */}
                    <Link href="/" className="flex items-center gap-2.5 flex-shrink-0 group">
                        <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-xl flex items-center justify-center shadow-md shadow-blue-500/20 group-hover:scale-105 transition">
                            OwO
                        </div>
                        <div>
                            <span className="font-extrabold text-lg text-gray-900 dark:text-white tracking-tight flex items-center gap-1.5">
                                OwOMarket
                                <span className="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                                    Central
                                </span>
                            </span>
                        </div>
                    </Link>

                    {/* Global Search Bar */}
                    <form onSubmit={handleSearch} className="flex-1 max-w-xl hidden md:flex items-center">
                        <div className="relative w-full">
                            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <HiOutlineMagnifyingGlass className="w-4 h-4" />
                            </div>
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={e => setSearchQuery(e.target.value)}
                                placeholder="Buscar productos, marcas o tiendas oficiales..."
                                className="w-full pl-10 pr-24 py-2 text-sm bg-gray-100/80 dark:bg-gray-800/80 text-gray-900 dark:text-white rounded-xl border-0 focus:ring-2 focus:ring-blue-500 transition placeholder-gray-400"
                            />
                            <button
                                type="submit"
                                className="absolute right-1.5 top-1.5 bottom-1.5 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition"
                            >
                                Buscar
                            </button>
                        </div>
                    </form>

                    {/* Navigation Links & Actions */}
                    <div className="flex items-center gap-2 sm:gap-3">
                        <Link
                            href="/marketplace"
                            className="hidden lg:flex items-center gap-1 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        >
                            Explorar Catálogo
                        </Link>

                        {/* Customer Auth / OwO Pass SSO */}
                        {isAuthenticated && customer ? (
                            <Dropdown
                                label=""
                                dismissOnClick={true}
                                renderTrigger={() => (
                                    <button className="flex items-center gap-2 p-1.5 sm:p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-xl transition-colors font-semibold">
                                        <div className="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shadow-sm">
                                            {((customer.name || 'U')[0]).toUpperCase()}
                                        </div>
                                        <span className="hidden lg:inline text-xs max-w-[110px] truncate">
                                            {customer.name}
                                        </span>
                                    </button>
                                )}
                            >
                                <DropdownHeader>
                                    <span className="block text-sm font-bold text-gray-900 dark:text-white">
                                        {customer.name}
                                    </span>
                                    <span className="block truncate text-xs text-gray-500">
                                        {customer.email}
                                    </span>
                                </DropdownHeader>
                                <DropdownItem href="/account/dashboard">📊 Mi Dashboard</DropdownItem>
                                <DropdownItem href="/account/orders">📦 Mis Pedidos & Tracking</DropdownItem>
                                <DropdownItem href="/account/invoices">🧾 Mis Facturas PDF</DropdownItem>
                                <DropdownItem href="/account/wishlist">❤️ Mis Favoritos</DropdownItem>
                                <DropdownItem href="/account/coupons">🎟️ Mis Cupones</DropdownItem>
                                <DropdownItem href="/account/profile">⚙️ Mi Perfil & Seguridad</DropdownItem>
                                <DropdownDivider />
                                <DropdownItem onClick={logout}>
                                    🚪 Cerrar Sesión
                                </DropdownItem>
                            </Dropdown>
                        ) : (
                            <button
                                onClick={() => openAuthModal()}
                                className="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-xl transition"
                            >
                                <HiOutlineUser className="w-4 h-4 text-blue-600" />
                                <span className="hidden sm:inline">OwO Pass</span>
                            </button>
                        )}

                        {/* Multi-Store Cart Button */}
                        <button
                            onClick={() => setIsDrawerOpen(true)}
                            className="relative flex items-center gap-2 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition"
                        >
                            <HiOutlineShoppingBag className="w-4 h-4" />
                            <span className="hidden sm:inline">Carrito</span>
                            {itemCount > 0 && (
                                <span className="px-1.5 py-0.5 text-[10px] font-black bg-white text-blue-600 rounded-full">
                                    {itemCount}
                                </span>
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
                            onChange={e => setSearchQuery(e.target.value)}
                            placeholder="Buscar en todo el marketplace..."
                            className="w-full pl-9 pr-16 py-2 text-xs bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl border-0 focus:ring-2 focus:ring-blue-500 placeholder-gray-400"
                        />
                        <button
                            type="submit"
                            className="absolute right-1 top-1 bottom-1 px-3 bg-blue-600 text-white rounded-lg text-[10px] font-bold"
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
        <footer className="bg-gray-900 text-gray-400 border-t border-gray-800 pt-12 pb-8 mt-20">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                    {/* Brand Info */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-sm flex items-center justify-center">
                                OwO
                            </div>
                            <span className="font-extrabold text-base text-white">OwOMarket Central</span>
                        </div>
                        <p className="text-xs leading-relaxed text-gray-400">
                            La plataforma multi-tienda definitiva donde puedes comprar en diferentes tiendas asociadas y pagar en una sola transacción unificada.
                        </p>
                    </div>

                    {/* Quick Links */}
                    <div>
                        <h4 className="text-xs font-bold text-white uppercase tracking-wider mb-3">Marketplace</h4>
                        <ul className="space-y-2 text-xs">
                            <li><Link href="/" className="hover:text-white transition">Inicio</Link></li>
                            <li><Link href="/marketplace" className="hover:text-white transition">Explorar Catálogo</Link></li>
                            <li><Link href="/cart" className="hover:text-white transition">Carrito Multi-Tienda</Link></li>
                            <li><Link href="/checkout" className="hover:text-white transition">Checkout Unificado</Link></li>
                        </ul>
                    </div>

                    {/* Security & Guarantees */}
                    <div>
                        <h4 className="text-xs font-bold text-white uppercase tracking-wider mb-3">Garantía y Seguridad</h4>
                        <ul className="space-y-2 text-xs">
                            <li className="flex items-center gap-2"><HiOutlineShieldCheck className="w-4 h-4 text-green-400" /> Compra 100% Protegida</li>
                            <li className="flex items-center gap-2"><HiOutlineSparkles className="w-4 h-4 text-purple-400" /> OwO Pass Universal SSO</li>
                            <li className="flex items-center gap-2"><HiOutlineBuildingStorefront className="w-4 h-4 text-blue-400" /> Tiendas Verificadas</li>
                        </ul>
                    </div>

                    {/* Payment Gateways */}
                    <div>
                        <h4 className="text-xs font-bold text-white uppercase tracking-wider mb-3">Métodos de Pago</h4>
                        <div className="space-y-2.5">
                            <div className="flex items-center gap-2 p-2 rounded-lg bg-gray-800/60 border border-gray-700/60">
                                <HiOutlineDevicePhoneMobile className="w-5 h-5 text-blue-400" />
                                <div>
                                    <p className="text-xs font-bold text-white">Pago Móvil</p>
                                    <p className="text-[10px] text-gray-400">Bancos Nacionales de Venezuela (Bs.)</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2 p-2 rounded-lg bg-gray-800/60 border border-gray-700/60">
                                <HiOutlineCurrencyDollar className="w-5 h-5 text-yellow-400" />
                                <div>
                                    <p className="text-xs font-bold text-white">Binance Pay</p>
                                    <p className="text-[10px] text-gray-400">Pagos instantáneos con USDT</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="border-t border-gray-800 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500">
                    <p>© {new Date().getFullYear()} OwOMarket. Todos los derechos reservados.</p>
                    <p className="mt-2 sm:mt-0">Diseñado con tecnología multi-inquilino de alto rendimiento.</p>
                </div>
            </div>
        </footer>
    );
};

const CentralLayoutContent: React.FC<CentralLayoutProps> = ({ children }) => {
    return (
        <div className="min-h-screen flex flex-col bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased selection:bg-blue-500 selection:text-white">
            <CentralNavbar />
            <main className="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                {children}
            </main>
            <CentralFooter />
            <CentralCartDrawer />
            <CustomerAuthModal />
        </div>
    );
};

const CentralLayout: FC<CentralLayoutProps> = ({ children }) => {
    return (
        <CustomerAuthProvider>
            <CentralCartProvider>
                <CentralLayoutContent>{children}</CentralLayoutContent>
            </CentralCartProvider>
        </CustomerAuthProvider>
    );
};

export default CentralLayout;
