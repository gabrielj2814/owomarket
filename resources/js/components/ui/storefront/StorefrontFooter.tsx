import React from 'react';
import {
    HiCreditCard,
    HiLockClosed,
    HiMail,
    HiOutlineShieldCheck,
    HiPhone,
    HiTruck,
} from 'react-icons/hi';
import { FaFacebook, FaInstagram, FaTwitter, FaWhatsapp } from 'react-icons/fa';

export interface StorefrontFooterProps {
    storeName?: string;
    storeEmail?: string;
    contactPhone?: string;
    address?: string;
    socialFacebook?: string;
    socialInstagram?: string;
    socialWhatsapp?: string;
    socialTwitter?: string;
    categories?: Array<{ id: string; name: string; slug: string }>;
}

export default function StorefrontFooter({
    storeName = 'Mi Tienda',
    storeEmail,
    contactPhone,
    address,
    socialFacebook,
    socialInstagram,
    socialWhatsapp,
    socialTwitter,
    categories = [],
}: StorefrontFooterProps) {
    const currentYear = new Date().getFullYear();

    return (
        <footer className="bg-gray-900 text-gray-400 text-sm mt-16 border-t border-gray-800 relative">
            {/* 1. Value Proposition Banner */}
            <div className="border-b border-gray-800 bg-gray-950/40">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 md:grid-cols-3 gap-6 text-center md:text-left">
                    <div className="flex items-center justify-center md:justify-start gap-4">
                        <div className="p-3 bg-blue-900/30 text-blue-400 rounded-xl">
                            <HiTruck className="w-6 h-6" />
                        </div>
                        <div>
                            <h4 className="text-white font-semibold text-sm">Envíos a Todo el País</h4>
                            <p className="text-xs text-gray-500">Despachos rápidos y seguimiento online.</p>
                        </div>
                    </div>

                    <div className="flex items-center justify-center md:justify-start gap-4">
                        <div className="p-3 bg-green-900/30 text-green-400 rounded-xl">
                            <HiLockClosed className="w-6 h-6" />
                        </div>
                        <div>
                            <h4 className="text-white font-semibold text-sm">Pago 100% Seguro</h4>
                            <p className="text-xs text-gray-500">Transacciones encriptadas con SSL.</p>
                        </div>
                    </div>

                    <div className="flex items-center justify-center md:justify-start gap-4">
                        <div className="p-3 bg-purple-900/30 text-purple-400 rounded-xl">
                            <HiOutlineShieldCheck className="w-6 h-6" />
                        </div>
                        <div>
                            <h4 className="text-white font-semibold text-sm">Garantía de Satisfacción</h4>
                            <p className="text-xs text-gray-500">Atención directa y compras protegidas.</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* 2. Main Footer Links */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                {/* Column 1: Brand & Contact */}
                <div className="space-y-4">
                    <h3 className="text-white text-lg font-bold tracking-tight">{storeName}</h3>
                    <p className="text-xs text-gray-400 leading-relaxed">
                        Tu tienda de confianza con los mejores productos, precios competitivos y atención personalizada.
                    </p>
                    <div className="space-y-2 text-xs">
                        {contactPhone && (
                            <div className="flex items-center gap-2">
                                <HiPhone className="w-4 h-4 text-blue-400" />
                                <span>{contactPhone}</span>
                            </div>
                        )}
                        {storeEmail && (
                            <div className="flex items-center gap-2">
                                <HiMail className="w-4 h-4 text-blue-400" />
                                <span>{storeEmail}</span>
                            </div>
                        )}
                        {address && (
                            <p className="text-gray-500 text-xs">
                                📍 {address}
                            </p>
                        )}
                    </div>
                </div>

                {/* Column 2: Navigation Links */}
                <div className="space-y-3">
                    <h4 className="text-white text-xs font-bold uppercase tracking-wider">Enlaces Rápidos</h4>
                    <ul className="space-y-2 text-xs">
                        <li>
                            <a href="/" className="hover:text-white transition-colors">Inicio</a>
                        </li>
                        <li>
                            <a href="/catalog" className="hover:text-white transition-colors">Catálogo de Productos</a>
                        </li>
                        <li>
                            <a href="/cart" className="hover:text-white transition-colors">Mi Carrito de Compras</a>
                        </li>
                        <li>
                            <a href="/checkout" className="hover:text-white transition-colors">Finalizar Compra</a>
                        </li>
                    </ul>
                </div>

                {/* Column 3: Categories */}
                <div className="space-y-3">
                    <h4 className="text-white text-xs font-bold uppercase tracking-wider">Categorías</h4>
                    <ul className="space-y-2 text-xs">
                        {categories.slice(0, 5).map((cat) => (
                            <li key={cat.id}>
                                <a
                                    href={`/catalog?category=${cat.slug}`}
                                    className="hover:text-white transition-colors"
                                >
                                    {cat.name}
                                </a>
                            </li>
                        ))}
                        {categories.length === 0 && (
                            <li className="text-gray-600">Explora nuestro catálogo</li>
                        )}
                    </ul>
                </div>

                {/* Column 4: Social & Secure Seals */}
                <div className="space-y-4">
                    <h4 className="text-white text-xs font-bold uppercase tracking-wider">Síguenos</h4>
                    <div className="flex items-center gap-3">
                        {socialInstagram && (
                            <a
                                href={socialInstagram}
                                target="_blank"
                                rel="noreferrer"
                                className="p-2 bg-gray-800 hover:bg-pink-600 text-gray-300 hover:text-white rounded-lg transition-colors"
                            >
                                <FaInstagram className="w-4 h-4" />
                            </a>
                        )}
                        {socialFacebook && (
                            <a
                                href={socialFacebook}
                                target="_blank"
                                rel="noreferrer"
                                className="p-2 bg-gray-800 hover:bg-blue-600 text-gray-300 hover:text-white rounded-lg transition-colors"
                            >
                                <FaFacebook className="w-4 h-4" />
                            </a>
                        )}
                        {socialTwitter && (
                            <a
                                href={socialTwitter}
                                target="_blank"
                                rel="noreferrer"
                                className="p-2 bg-gray-800 hover:bg-sky-500 text-gray-300 hover:text-white rounded-lg transition-colors"
                            >
                                <FaTwitter className="w-4 h-4" />
                            </a>
                        )}
                        {socialWhatsapp && (
                            <a
                                href={socialWhatsapp.startsWith('http') ? socialWhatsapp : `https://wa.me/${socialWhatsapp.replace(/[^0-9]/g, '')}`}
                                target="_blank"
                                rel="noreferrer"
                                className="p-2 bg-gray-800 hover:bg-green-600 text-gray-300 hover:text-white rounded-lg transition-colors"
                            >
                                <FaWhatsapp className="w-4 h-4" />
                            </a>
                        )}
                    </div>

                    <div className="pt-2">
                        <span className="text-[11px] text-gray-500 block mb-1.5">Métodos de pago aceptados:</span>
                        <div className="flex items-center gap-2 text-gray-400">
                            <span className="bg-gray-800 px-2 py-1 rounded text-[10px] font-bold">WEBPAY</span>
                            <span className="bg-gray-800 px-2 py-1 rounded text-[10px] font-bold">VISA</span>
                            <span className="bg-gray-800 px-2 py-1 rounded text-[10px] font-bold">MASTERCARD</span>
                            <span className="bg-gray-800 px-2 py-1 rounded text-[10px] font-bold">TRANSFERENCIA</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* 3. Bottom Copyright Bar */}
            <div className="border-t border-gray-800 py-6 text-center text-xs text-gray-600">
                <p>© {currentYear} {storeName}. Todos los derechos reservados. Desarrollado en la plataforma OwoMarket.</p>
            </div>

            {/* 4. Floating WhatsApp CTA Widget */}
            {socialWhatsapp && (
                <a
                    href={socialWhatsapp.startsWith('http') ? socialWhatsapp : `https://wa.me/${socialWhatsapp.replace(/[^0-9]/g, '')}?text=Hola!%20Tengo%20una%20consulta%20sobre%20la%20tienda`}
                    target="_blank"
                    rel="noreferrer"
                    className="fixed bottom-6 right-6 z-40 p-3.5 bg-green-500 hover:bg-green-600 text-white rounded-full shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300 flex items-center justify-center group"
                    title="Hablar por WhatsApp"
                >
                    <FaWhatsapp className="w-6 h-6" />
                    <span className="max-w-0 overflow-hidden whitespace-nowrap group-hover:max-w-xs group-hover:ml-2 text-xs font-bold transition-all duration-300 ease-in-out">
                        ¿Necesitas ayuda?
                    </span>
                </a>
            )}
        </footer>
    );
}
