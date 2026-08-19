import React from 'react';
import { Head, Link } from '@inertiajs/react';
import CentralLayout from '@/components/layouts/CentralLayout';
import { useCentralCart } from '@/contexts/CentralCartContext';
import {
    HiOutlineShoppingBag,
    HiOutlineTrash,
    HiOutlineBuildingStorefront,
    HiArrowRight,
    HiOutlineShieldCheck,
    HiOutlineArrowLeft,
} from 'react-icons/hi2';

interface CentralCartPageProps {
    domain?: string;
}

const CentralCartPage: React.FC<CentralCartPageProps> = ({ domain }) => {
    const {
        items,
        getItemsByStore,
        getSubtotal,
        getItemCount,
        updateQuantity,
        removeItem,
        clearStoreItems,
        clearCart,
    } = useCentralCart();

    const storeGroups = getItemsByStore();
    const subtotal = getSubtotal();
    const totalCount = getItemCount();

    return (
        <CentralLayout>
            <Head title="Carrito Multi-Tienda - OwOMarket Central" />

            <div className="space-y-8">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                            <HiOutlineShoppingBag className="w-8 h-8 text-blue-600" />
                            Carrito de Compras Multi-Tienda
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Tus artículos están organizados por tienda vendedora y se pagarán en una sola orden consolidada.
                        </p>
                    </div>

                    {items.length > 0 && (
                        <button
                            onClick={clearCart}
                            className="text-xs font-semibold text-red-500 hover:text-red-700 dark:text-red-400"
                        >
                            Vaciar todo el carrito
                        </button>
                    )}
                </div>

                {items.length === 0 ? (
                    <div className="text-center py-24 rounded-3xl border border-dashed border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-8 space-y-4">
                        <div className="w-20 h-20 bg-blue-50 dark:bg-blue-900/30 text-blue-500 rounded-full flex items-center justify-center mx-auto">
                            <HiOutlineShoppingBag className="w-10 h-10" />
                        </div>
                        <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                            Tu carrito está completamente vacío
                        </h2>
                        <p className="text-xs text-gray-500 max-w-sm mx-auto">
                            Descubre los miles de productos disponibles en nuestras tiendas oficiales asociadas.
                        </p>
                        <Link
                            href="/marketplace"
                            className="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition shadow-md shadow-blue-500/20"
                        >
                            <HiOutlineArrowLeft className="w-4 h-4" />
                            Explorar el Marketplace
                        </Link>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        {/* Store Groups Column */}
                        <div className="lg:col-span-8 space-y-6">
                            {storeGroups.map(group => (
                                <div
                                    key={group.tenant_id}
                                    className="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm"
                                >
                                    {/* Store Group Header */}
                                    <div className="p-4 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                                        <div className="flex items-center gap-2.5">
                                            <HiOutlineBuildingStorefront className="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                            <div>
                                                <h3 className="text-sm font-black text-gray-900 dark:text-white">
                                                    {group.tenant_name}
                                                </h3>
                                                <p className="text-[11px] text-gray-500 dark:text-gray-400">
                                                    {group.items_count} {group.items_count === 1 ? 'artículo' : 'artículos'}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-4">
                                            <span className="text-xs font-bold text-gray-900 dark:text-white">
                                                Subtotal: ${group.subtotal.toFixed(2)}
                                            </span>
                                            <button
                                                onClick={() => clearStoreItems(group.tenant_id)}
                                                className="text-xs text-gray-400 hover:text-red-500"
                                                title="Eliminar productos de esta tienda"
                                            >
                                                <HiOutlineTrash className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    {/* Items Table / List */}
                                    <div className="p-4 divide-y divide-gray-100 dark:divide-gray-800">
                                        {group.items.map(item => (
                                            <div
                                                key={item.id}
                                                className="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                                            >
                                                <div className="flex items-center gap-4">
                                                    {item.image ? (
                                                        <img
                                                            src={item.image}
                                                            alt={item.product_name}
                                                            className="w-16 h-16 object-cover rounded-xl border border-gray-200 dark:border-gray-700 flex-shrink-0"
                                                        />
                                                    ) : (
                                                        <div className="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-center text-gray-400 text-xs font-bold flex-shrink-0">
                                                            OwO
                                                        </div>
                                                    )}
                                                    <div>
                                                        <Link
                                                            href={`/product/${item.slug}`}
                                                            className="text-sm font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition line-clamp-1"
                                                        >
                                                            {item.product_name}
                                                        </Link>
                                                        <p className="text-xs font-black text-blue-600 dark:text-blue-400 mt-0.5">
                                                            ${item.price.toFixed(2)} USD
                                                        </p>
                                                        {item.sku && (
                                                            <p className="text-[10px] text-gray-400">SKU: {item.sku}</p>
                                                        )}
                                                    </div>
                                                </div>

                                                <div className="flex items-center justify-between sm:justify-end gap-6">
                                                    {/* Quantity Controls */}
                                                    <div className="flex items-center border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800">
                                                        <button
                                                            type="button"
                                                            onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                                            className="px-3 py-1 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-l-xl"
                                                        >
                                                            -
                                                        </button>
                                                        <span className="px-3 text-xs font-bold text-gray-900 dark:text-white">
                                                            {item.quantity}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                                            className="px-3 py-1 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-r-xl"
                                                        >
                                                            +
                                                        </button>
                                                    </div>

                                                    <div className="text-right min-w-[70px]">
                                                        <span className="text-sm font-black text-gray-900 dark:text-white">
                                                            ${(item.price * item.quantity).toFixed(2)}
                                                        </span>
                                                    </div>

                                                    <button
                                                        onClick={() => removeItem(item.id)}
                                                        className="p-1.5 text-gray-400 hover:text-red-500 rounded-lg transition"
                                                        title="Eliminar producto"
                                                    >
                                                        <HiOutlineTrash className="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Order Summary Column */}
                        <div className="lg:col-span-4 space-y-6">
                            <div className="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-6 shadow-sm sticky top-24">
                                <h3 className="text-base font-black text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-3">
                                    Resumen de la Orden
                                </h3>

                                <div className="space-y-3 text-xs">
                                    <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                        <span>Total artículos:</span>
                                        <span className="font-bold text-gray-900 dark:text-white">{totalCount}</span>
                                    </div>
                                    <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                        <span>Tiendas involucradas:</span>
                                        <span className="font-bold text-gray-900 dark:text-white">{storeGroups.length}</span>
                                    </div>
                                    <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                        <span>Subtotal consolidado:</span>
                                        <span className="font-bold text-gray-900 dark:text-white">${subtotal.toFixed(2)} USD</span>
                                    </div>
                                    <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                        <span>Envío estimado:</span>
                                        <span className="text-green-600 dark:text-green-400 font-bold">Por coordinar</span>
                                    </div>
                                </div>

                                <div className="pt-4 border-t border-gray-200 dark:border-gray-800 flex justify-between items-center">
                                    <span className="text-sm font-bold text-gray-900 dark:text-white">Total a Pagar:</span>
                                    <span className="text-2xl font-black text-blue-600 dark:text-blue-400">
                                        ${subtotal.toFixed(2)} USD
                                    </span>
                                </div>

                                <Link
                                    href="/checkout"
                                    className="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm shadow-lg shadow-blue-500/20 transition flex items-center justify-center gap-2"
                                >
                                    Proceder al Checkout Unificado
                                    <HiArrowRight className="w-4 h-4" />
                                </Link>

                                <div className="pt-2 flex items-center justify-center gap-2 text-[11px] text-gray-400 text-center">
                                    <HiOutlineShieldCheck className="w-4 h-4 text-green-500 inline" />
                                    <span>Garantía de compra protegida OwOMarket</span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </CentralLayout>
    );
};

export default CentralCartPage;
