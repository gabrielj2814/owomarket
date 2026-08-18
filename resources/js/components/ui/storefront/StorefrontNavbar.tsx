import React, { useState } from 'react';
import { useCart } from '@/contexts/CartContext';
import {
    Badge,
    Button,
    Dropdown,
    DropdownDivider,
    DropdownHeader,
    DropdownItem,
    TextInput,
} from 'flowbite-react';
import {
    HiChevronDown,
    HiMail,
    HiOutlineSearch,
    HiOutlineShoppingBag,
    HiOutlineUser,
    HiPhone,
    HiShoppingBag,
    HiUser,
} from 'react-icons/hi';
import { FaFacebook, FaInstagram, FaWhatsapp } from 'react-icons/fa';

export interface StorefrontNavbarProps {
    storeName?: string;
    logoUrl?: string;
    contactPhone?: string;
    storeEmail?: string;
    currency?: string;
    socialFacebook?: string;
    socialInstagram?: string;
    socialWhatsapp?: string;
    categories?: Array<{ id: string; name: string; slug: string }>;
    authUser?: { id: string; name: string; email: string } | null;
}

export default function StorefrontNavbar({
    storeName = 'Mi Tienda',
    logoUrl,
    contactPhone,
    storeEmail,
    currency = 'USD',
    socialFacebook,
    socialInstagram,
    socialWhatsapp,
    categories = [],
    authUser = null,
}: StorefrontNavbarProps) {
    const { totalCount, openDrawer } = useCart();
    const [searchQuery, setSearchQuery] = useState<string>('');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            window.location.href = `/catalog?search=${encodeURIComponent(searchQuery.trim())}`;
        }
    };

    return (
        <header className="sticky top-0 z-40 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 shadow-sm">
            {/* 1. Top Mini Bar */}
            <div className="bg-gray-900 text-gray-300 text-xs py-1.5 px-4 sm:px-8 border-b border-gray-800">
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
                    {/* Left: Contact Info */}
                    <div className="flex items-center gap-4">
                        {contactPhone && (
                            <a
                                href={`tel:${contactPhone}`}
                                className="flex items-center gap-1 hover:text-white transition-colors"
                            >
                                <HiPhone className="w-3.5 h-3.5 text-blue-400" />
                                <span>{contactPhone}</span>
                            </a>
                        )}
                        {storeEmail && (
                            <a
                                href={`mailto:${storeEmail}`}
                                className="hidden md:flex items-center gap-1 hover:text-white transition-colors"
                            >
                                <HiMail className="w-3.5 h-3.5 text-blue-400" />
                                <span>{storeEmail}</span>
                            </a>
                        )}
                    </div>

                    {/* Right: Currency & Socials */}
                    <div className="flex items-center gap-3">
                        <span className="bg-gray-800 text-gray-300 px-2 py-0.5 rounded text-[11px] font-semibold tracking-wider">
                            {currency}
                        </span>

                        <div className="flex items-center gap-2 text-gray-400">
                            {socialWhatsapp && (
                                <a
                                    href={socialWhatsapp.startsWith('http') ? socialWhatsapp : `https://wa.me/${socialWhatsapp.replace(/[^0-9]/g, '')}`}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="hover:text-green-400 transition-colors"
                                    title="WhatsApp"
                                >
                                    <FaWhatsapp className="w-3.5 h-3.5" />
                                </a>
                            )}
                            {socialInstagram && (
                                <a
                                    href={socialInstagram}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="hover:text-pink-400 transition-colors"
                                    title="Instagram"
                                >
                                    <FaInstagram className="w-3.5 h-3.5" />
                                </a>
                            )}
                            {socialFacebook && (
                                <a
                                    href={socialFacebook}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="hover:text-blue-400 transition-colors"
                                    title="Facebook"
                                >
                                    <FaFacebook className="w-3.5 h-3.5" />
                                </a>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* 2. Main Store Navbar */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between gap-4 sm:gap-8">
                {/* Logo / Store Name */}
                <a href="/" className="flex items-center gap-3 flex-shrink-0">
                    {logoUrl ? (
                        <img
                            src={logoUrl}
                            alt={storeName}
                            className="h-9 sm:h-11 max-w-[180px] object-contain"
                            onError={(e) => {
                                (e.target as HTMLImageElement).style.display = 'none';
                            }}
                        />
                    ) : (
                        <div className="flex items-center gap-2">
                            <div className="p-2 bg-gradient-to-tr from-blue-600 to-indigo-600 text-white rounded-lg shadow-md shadow-blue-500/20">
                                <HiShoppingBag className="w-5 h-5" />
                            </div>
                            <span className="text-xl sm:text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                                {storeName}
                            </span>
                        </div>
                    )}
                </a>

                {/* Search Bar */}
                <form
                    onSubmit={handleSearch}
                    className="hidden md:flex flex-1 max-w-xl relative items-center"
                >
                    <TextInput
                        id="storefront_search"
                        type="search"
                        icon={HiOutlineSearch}
                        placeholder="Buscar productos, marcas, categorías..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="w-full"
                    />
                </form>

                {/* Right Action Icons */}
                <div className="flex items-center gap-2 sm:gap-3">
                    {/* User Account / Auth */}
                    {authUser ? (
                        <Dropdown
                            label=""
                            dismissOnClick={false}
                            renderTrigger={() => (
                                <button className="flex items-center gap-2 p-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                                    <HiUser className="w-5 h-5 text-blue-600" />
                                    <span className="hidden lg:inline text-xs font-semibold max-w-[100px] truncate">
                                        {authUser.name}
                                    </span>
                                    <HiChevronDown className="w-3 h-3 text-gray-400 hidden lg:inline" />
                                </button>
                            )}
                        >
                            <DropdownHeader>
                                <span className="block text-sm font-semibold">{authUser.name}</span>
                                <span className="block truncate text-xs text-gray-500">{authUser.email}</span>
                            </DropdownHeader>
                            <DropdownItem href="/catalog">Explorar Catálogo</DropdownItem>
                            <DropdownItem href="/cart">Mi Carrito</DropdownItem>
                            <DropdownDivider />
                            <DropdownItem href="/auth/login">Cerrar Sesión</DropdownItem>
                        </Dropdown>
                    ) : (
                        <a
                            href="/auth/login"
                            className="flex items-center gap-1.5 p-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg text-xs font-semibold transition-colors"
                        >
                            <HiOutlineUser className="w-5 h-5" />
                            <span className="hidden sm:inline">Ingresar</span>
                        </a>
                    )}

                    {/* Cart Trigger Button */}
                    <button
                        type="button"
                        onClick={openDrawer}
                        className="relative flex items-center gap-2 p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-xl transition-all font-semibold"
                        aria-label="Abrir carrito de compras"
                    >
                        <HiOutlineShoppingBag className="w-5 h-5" />
                        <span className="hidden sm:inline text-xs">Carrito</span>
                        {totalCount > 0 && (
                            <span className="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[11px] font-bold text-white shadow-md animate-pulse">
                                {totalCount}
                            </span>
                        )}
                    </button>
                </div>
            </div>

            {/* 3. Category & Navigation Strip */}
            <nav className="border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 px-4 sm:px-8">
                <div className="max-w-7xl mx-auto flex items-center justify-between overflow-x-auto py-2 scrollbar-none text-xs font-semibold">
                    <div className="flex items-center gap-6 flex-shrink-0">
                        <a
                            href="/"
                            className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                        >
                            Inicio
                        </a>
                        <a
                            href="/catalog"
                            className="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                        >
                            Catálogo Completo
                        </a>
                        {categories.slice(0, 5).map((cat) => (
                            <a
                                key={cat.id}
                                href={`/catalog?category=${cat.slug}`}
                                className="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            >
                                {cat.name}
                            </a>
                        ))}
                    </div>

                    <a
                        href="/catalog?filter=on_sale"
                        className="text-red-600 dark:text-red-400 font-bold hover:underline flex-shrink-0 hidden sm:inline"
                    >
                        🔥 Ofertas Especiales
                    </a>
                </div>
            </nav>
        </header>
    );
}
