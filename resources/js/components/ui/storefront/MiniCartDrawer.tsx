import React from 'react';
import { useCart } from '@/contexts/CartContext';
import { Badge, Button } from 'flowbite-react';
import {
    HiArrowRight,
    HiMinus,
    HiOutlineShoppingBag,
    HiPlus,
    HiShoppingCart,
    HiTrash,
    HiX,
} from 'react-icons/hi';

export default function MiniCartDrawer() {
    const {
        items,
        isDrawerOpen,
        closeDrawer,
        removeItem,
        updateQuantity,
        totalCount,
        subtotal,
        discountAmount,
        total,
        coupon,
        formatPrice,
    } = useCart();

    if (!isDrawerOpen) return null;

    return (
        <div className="fixed inset-0 z-50 overflow-hidden">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity duration-300"
                onClick={closeDrawer}
            />

            <div className="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div className="w-screen max-w-md bg-white dark:bg-gray-900 shadow-2xl flex flex-col transform transition-transform duration-300 ease-in-out">
                    {/* Header */}
                    <div className="p-4 sm:p-6 border-b dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                        <div className="flex items-center gap-2">
                            <div className="p-2 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-lg">
                                <HiOutlineShoppingBag className="w-5 h-5" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                    Tu Carrito
                                </h3>
                                <p className="text-xs text-gray-500">
                                    {totalCount} {totalCount === 1 ? 'producto' : 'productos'}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={closeDrawer}
                            className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        >
                            <HiX className="w-6 h-6" />
                        </button>
                    </div>

                    {/* Content */}
                    <div className="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
                        {items.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center text-center py-12">
                                <div className="p-4 bg-gray-100 dark:bg-gray-800 rounded-full text-gray-400 mb-4">
                                    <HiOutlineShoppingBag className="w-12 h-12" />
                                </div>
                                <h4 className="text-base font-semibold text-gray-900 dark:text-white mb-1">
                                    Tu carrito está vacío
                                </h4>
                                <p className="text-sm text-gray-500 dark:text-gray-400 max-w-xs mb-6">
                                    Parece que aún no has agregado productos a tu compra.
                                </p>
                                <Button
                                    color="blue"
                                    onClick={() => {
                                        closeDrawer();
                                        window.location.href = '/catalog';
                                    }}
                                >
                                    Explorar Catálogo
                                </Button>
                            </div>
                        ) : (
                            <div className="divide-y dark:divide-gray-800">
                                {items.map((item) => (
                                    <div key={item.id} className="py-4 flex gap-4 items-start">
                                        {/* Image */}
                                        <div className="w-16 h-16 sm:w-20 sm:h-20 rounded-lg bg-gray-100 dark:bg-gray-800 overflow-hidden flex-shrink-0 border dark:border-gray-700">
                                            {item.image ? (
                                                <img
                                                    src={item.image}
                                                    alt={item.name}
                                                    className="w-full h-full object-cover"
                                                    onError={(e) => {
                                                        (e.target as HTMLImageElement).src =
                                                            'https://via.placeholder.com/80x80?text=Producto';
                                                    }}
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center text-gray-400">
                                                    <HiOutlineShoppingBag className="w-6 h-6" />
                                                </div>
                                            )}
                                        </div>

                                        {/* Details */}
                                        <div className="flex-1 min-w-0">
                                            <h4 className="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                {item.name}
                                            </h4>

                                            {/* Attributes / Variant */}
                                            {item.attributes && Object.keys(item.attributes).length > 0 && (
                                                <div className="flex flex-wrap gap-1 mt-1">
                                                    {Object.entries(item.attributes).map(([key, val]) => (
                                                        <span
                                                            key={key}
                                                            className="text-[11px] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 rounded"
                                                        >
                                                            {key}: {val}
                                                        </span>
                                                    ))}
                                                </div>
                                            )}

                                            {/* Price */}
                                            <div className="mt-1 flex items-baseline gap-2">
                                                <span className="text-sm font-bold text-gray-900 dark:text-white">
                                                    {formatPrice(item.price)}
                                                </span>
                                                {item.originalPrice && item.originalPrice > item.price && (
                                                    <span className="text-xs line-through text-gray-400">
                                                        {formatPrice(item.originalPrice)}
                                                    </span>
                                                )}
                                            </div>

                                            {/* Quantity & Delete Controls */}
                                            <div className="mt-3 flex items-center justify-between">
                                                <div className="flex items-center border dark:border-gray-700 rounded-md">
                                                    <button
                                                        type="button"
                                                        onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                                        className="p-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-l-md"
                                                    >
                                                        <HiMinus className="w-3.5 h-3.5" />
                                                    </button>
                                                    <span className="px-3 text-xs font-semibold text-gray-800 dark:text-gray-200">
                                                        {item.quantity}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                                        disabled={item.quantity >= item.maxStock}
                                                        className="p-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-r-md disabled:opacity-40"
                                                    >
                                                        <HiPlus className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>

                                                <button
                                                    type="button"
                                                    onClick={() => removeItem(item.id)}
                                                    className="text-xs text-red-500 hover:text-red-700 flex items-center gap-1"
                                                >
                                                    <HiTrash className="w-3.5 h-3.5" />
                                                    <span>Eliminar</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Footer */}
                    {items.length > 0 && (
                        <div className="p-4 sm:p-6 border-t dark:border-gray-800 bg-gray-50/80 dark:bg-gray-800/80 space-y-4">
                            {/* Summary */}
                            <div className="space-y-1.5 text-sm">
                                <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span>Subtotal</span>
                                    <span>{formatPrice(subtotal)}</span>
                                </div>
                                {coupon && (
                                    <div className="flex justify-between text-green-600 font-medium">
                                        <span>Descuento ({coupon.code})</span>
                                        <span>- {formatPrice(discountAmount)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between text-base font-bold text-gray-900 dark:text-white pt-2 border-t dark:border-gray-700">
                                    <span>Total Estimado</span>
                                    <span>{formatPrice(total)}</span>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="space-y-2">
                                <Button
                                    color="blue"
                                    className="w-full"
                                    onClick={() => {
                                        closeDrawer();
                                        window.location.href = '/checkout';
                                    }}
                                >
                                    <span className="flex items-center justify-center gap-2 font-semibold">
                                        Finalizar Compra
                                        <HiArrowRight className="w-4 h-4" />
                                    </span>
                                </Button>
                                <Button
                                    color="gray"
                                    className="w-full"
                                    onClick={() => {
                                        closeDrawer();
                                        window.location.href = '/cart';
                                    }}
                                >
                                    Ver Carrito Detallado
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
