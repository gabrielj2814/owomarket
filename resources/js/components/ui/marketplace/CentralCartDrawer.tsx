import React from 'react';
import { Link } from '@inertiajs/react';
import { useCentralCart } from '@/contexts/CentralCartContext';
import { HiOutlineShoppingBag, HiOutlineTrash, HiXMark, HiArrowRight, HiOutlineBuildingStorefront } from 'react-icons/hi2';

const CentralCartDrawer: React.FC = () => {
    const { isDrawerOpen, setIsDrawerOpen, items, updateQuantity, removeItem, getItemsByStore, getSubtotal, getItemCount } = useCentralCart();

    if (!isDrawerOpen) return null;

    const storeGroups = getItemsByStore();
    const subtotal = getSubtotal();
    const totalCount = getItemCount();

    return (
        <div className="fixed inset-0 z-50 overflow-hidden">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300"
                onClick={() => setIsDrawerOpen(false)}
            />

            <div className="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div className="w-screen max-w-md bg-white dark:bg-gray-900 shadow-2xl flex flex-col">
                    {/* Header */}
                    <div className="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-blue-600/5 to-purple-600/5">
                        <div className="flex items-center gap-2">
                            <div className="p-2 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-lg">
                                <HiOutlineShoppingBag className="w-5 h-5" />
                            </div>
                            <div>
                                <h2 className="text-base font-bold text-gray-900 dark:text-white">
                                    Carrito Multi-Tienda
                                </h2>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    {totalCount} {totalCount === 1 ? 'producto' : 'productos'} de {storeGroups.length} {storeGroups.length === 1 ? 'tienda' : 'tiendas'}
                                </p>
                            </div>
                        </div>
                        <button
                            onClick={() => setIsDrawerOpen(false)}
                            className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        >
                            <HiXMark className="w-5 h-5" />
                        </button>
                    </div>

                    {/* Cart Items / Groups */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-6">
                        {storeGroups.length === 0 ? (
                            <div className="text-center py-16 space-y-4">
                                <div className="w-16 h-16 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded-full flex items-center justify-center mx-auto">
                                    <HiOutlineShoppingBag className="w-8 h-8" />
                                </div>
                                <div>
                                    <h3 className="font-semibold text-gray-900 dark:text-white text-base">Tu carrito está vacío</h3>
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Explora las tiendas oficiales y añade tus productos favoritos.</p>
                                </div>
                                <Link
                                    href="/marketplace"
                                    onClick={() => setIsDrawerOpen(false)}
                                    className="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl shadow-sm transition"
                                >
                                    Explorar Marketplace
                                </Link>
                            </div>
                        ) : (
                            storeGroups.map(group => (
                                <div
                                    key={group.tenant_id}
                                    className="rounded-2xl border border-gray-200 dark:border-gray-800 p-4 bg-gray-50/50 dark:bg-gray-800/40 space-y-3"
                                >
                                    {/* Store Header */}
                                    <div className="flex items-center justify-between border-b border-gray-200/60 dark:border-gray-700/60 pb-2">
                                        <div className="flex items-center gap-2">
                                            <HiOutlineBuildingStorefront className="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                            <span className="font-bold text-sm text-gray-900 dark:text-white">
                                                {group.tenant_name}
                                            </span>
                                        </div>
                                        <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            Subtotal: ${group.subtotal.toFixed(2)}
                                        </span>
                                    </div>

                                    {/* Items in Store */}
                                    <div className="space-y-3">
                                        {group.items.map(item => (
                                            <div
                                                key={item.id}
                                                className="flex gap-3 bg-white dark:bg-gray-800 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700"
                                            >
                                                {item.image ? (
                                                    <img
                                                        src={item.image}
                                                        alt={item.product_name}
                                                        className="w-14 h-14 object-cover rounded-lg flex-shrink-0"
                                                    />
                                                ) : (
                                                    <div className="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center text-gray-400 text-xs font-semibold flex-shrink-0">
                                                        OwO
                                                    </div>
                                                )}

                                                <div className="flex-1 min-w-0">
                                                    <h4 className="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                                        {item.product_name}
                                                    </h4>
                                                    <p className="text-xs font-bold text-blue-600 dark:text-blue-400 mt-0.5">
                                                        ${item.price.toFixed(2)}
                                                    </p>

                                                    {/* Controls */}
                                                    <div className="flex items-center justify-between mt-2">
                                                        <div className="flex items-center border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900">
                                                            <button
                                                                type="button"
                                                                onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                                                className="px-2 py-0.5 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-l-lg"
                                                            >
                                                                -
                                                            </button>
                                                            <span className="px-2 text-xs font-medium text-gray-900 dark:text-white">
                                                                {item.quantity}
                                                            </span>
                                                            <button
                                                                type="button"
                                                                onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                                                className="px-2 py-0.5 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-r-lg"
                                                            >
                                                                +
                                                            </button>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            onClick={() => removeItem(item.id)}
                                                            className="text-gray-400 hover:text-red-500 p-1 transition"
                                                            title="Eliminar producto"
                                                        >
                                                            <HiOutlineTrash className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Footer / Total & Checkout */}
                    {storeGroups.length > 0 && (
                        <div className="p-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-900/80 space-y-3">
                            <div className="flex justify-between items-center text-sm">
                                <span className="text-gray-500 dark:text-gray-400">Total Consolidado:</span>
                                <span className="text-xl font-black text-gray-900 dark:text-white">
                                    ${subtotal.toFixed(2)} USD
                                </span>
                            </div>

                            <div className="grid grid-cols-2 gap-2">
                                <Link
                                    href="/cart"
                                    onClick={() => setIsDrawerOpen(false)}
                                    className="w-full text-center py-2.5 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                >
                                    Ver Carrito Completo
                                </Link>
                                <Link
                                    href="/checkout"
                                    onClick={() => setIsDrawerOpen(false)}
                                    className="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition"
                                >
                                    Pagar Ahora
                                    <HiArrowRight className="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default CentralCartDrawer;
