import React, { useState } from 'react';
import StorefrontLayout from '@/components/layouts/StorefrontLayout';
import ProductCard from '@/components/ui/storefront/ProductCard';
import { useCart } from '@/contexts/CartContext';
import CouponServices from '@/Services/CouponServices';
import { StorefrontCartPageProps } from '@/types/models/Storefront';
import {
    Alert,
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Label,
    Spinner,
    TextInput,
} from 'flowbite-react';
import {
    HiArrowLeft,
    HiArrowRight,
    HiCheck,
    HiHome,
    HiLockClosed,
    HiMinus,
    HiOutlineShoppingBag,
    HiOutlineTicket,
    HiPlus,
    HiShieldCheck,
    HiTag,
    HiTrash,
    HiTruck,
} from 'react-icons/hi';

function CartPageContent({
    domain,
    store_settings,
    categories = [],
    recommended_products = [],
    auth_user = null,
}: StorefrontCartPageProps) {
    const {
        items,
        subtotal,
        discountAmount,
        total,
        coupon,
        applyCoupon,
        removeCoupon,
        updateQuantity,
        removeItem,
        clearCart,
        formatPrice,
    } = useCart();

    const [couponInput, setCouponInput] = useState<string>('');
    const [isApplyingCoupon, setIsApplyingCoupon] = useState<boolean>(false);
    const [couponMessage, setCouponMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const handleApplyCoupon = async (e: React.FormEvent) => {
        e.preventDefault();
        const code = couponInput.trim().toUpperCase();
        if (!code) return;

        setIsApplyingCoupon(true);
        setCouponMessage(null);

        try {
            const res = await CouponServices.validate({
                code: code,
                order_subtotal: subtotal,
            });

            const apiData = res.data;
            if (apiData && apiData.code === 200 && apiData.data) {
                const couponData = apiData.data;
                applyCoupon({
                    code: couponData.coupon?.code || code,
                    type: (couponData.coupon?.type as 'percentage' | 'fixed_amount') || 'fixed_amount',
                    value: Number(couponData.coupon?.value || 0),
                    discountAmount: Number(couponData.discount_amount || 0),
                    description: couponData.message || `Cupón ${code} aplicado`,
                });
                setCouponMessage({
                    type: 'success',
                    text: `¡Cupón ${code} aplicado! Descuento: ${formatPrice(couponData.discount_amount)}`,
                });
                setCouponInput('');
            } else {
                setCouponMessage({
                    type: 'error',
                    text: apiData?.message || 'El cupón ingresado no es válido o ha expirado.',
                });
            }
        } catch {
            setCouponMessage({
                type: 'error',
                text: 'Error al conectar con el servidor de validación.',
            });
        } finally {
            setIsApplyingCoupon(false);
        }
    };

    return (
        <>
            <div className="space-y-8">
                {/* Breadcrumb */}
                <Breadcrumb>
                    <BreadcrumbItem href="/" icon={HiHome}>
                        Inicio
                    </BreadcrumbItem>
                    <BreadcrumbItem href="/catalog">Catálogo</BreadcrumbItem>
                    <BreadcrumbItem>Carrito de Compras</BreadcrumbItem>
                </Breadcrumb>

                {/* Page Title & Counter */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b dark:border-gray-800 pb-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white">
                            Tu Carrito de Compras
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-0.5">
                            {items.length === 0
                                ? 'No tienes productos en tu carrito.'
                                : `Tienes ${items.length} artículo${items.length > 1 ? 's' : ''} en tu orden.`}
                        </p>
                    </div>

                    {items.length > 0 && (
                        <button
                            type="button"
                            onClick={clearCart}
                            className="text-xs text-red-600 dark:text-red-400 hover:underline font-semibold flex items-center gap-1"
                        >
                            <HiTrash className="w-4 h-4" />
                            Vaciar Carrito Completo
                        </button>
                    )}
                </div>

                {/* Main Content View */}
                {items.length === 0 ? (
                    /* Empty State View */
                    <div className="py-20 px-4 text-center bg-white dark:bg-gray-900 rounded-3xl border border-dashed dark:border-gray-800 space-y-5">
                        <div className="w-20 h-20 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto shadow-inner">
                            <HiOutlineShoppingBag className="w-10 h-10" />
                        </div>
                        <div className="space-y-2">
                            <h2 className="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                                Tu carrito está vacío
                            </h2>
                            <p className="text-sm text-gray-500 max-w-md mx-auto">
                                Explora nuestro catálogo de productos y agrega lo que más te guste para comenzar tu compra.
                            </p>
                        </div>
                        <Button
                            color="blue"
                            size="lg"
                            className="mx-auto shadow-md"
                            onClick={() => (window.location.href = '/catalog')}
                        >
                            <span className="flex items-center gap-2 font-bold">
                                Explorar Catálogo de Productos
                                <HiArrowRight className="w-5 h-5" />
                            </span>
                        </Button>
                    </div>
                ) : (
                    /* Cart Items + Summary (2 Columns) */
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                        {/* Left Column: Items List */}
                        <div className="lg:col-span-2 space-y-4">
                            <div className="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden divide-y dark:divide-gray-800">
                                {items.map((item) => (
                                    <div
                                        key={item.id}
                                        className="p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                                    >
                                        {/* Product Thumbnail & Basic Info */}
                                        <div className="flex items-center gap-4 flex-1 min-w-0">
                                            <a
                                                href={`/product/${item.slug}`}
                                                className="w-16 h-16 sm:w-20 sm:h-20 bg-gray-50 dark:bg-gray-800 rounded-xl overflow-hidden flex-shrink-0 border dark:border-gray-700 flex items-center justify-center"
                                            >
                                                {item.image ? (
                                                    <img
                                                        src={item.image}
                                                        alt={item.name}
                                                        className="w-full h-full object-contain p-1"
                                                    />
                                                ) : (
                                                    <HiOutlineShoppingBag className="w-8 h-8 text-gray-400" />
                                                )}
                                            </a>

                                            <div className="space-y-1 min-w-0">
                                                <h3 className="text-sm sm:text-base font-bold text-gray-900 dark:text-white line-clamp-1 hover:text-blue-600">
                                                    <a href={`/product/${item.slug}`}>{item.name}</a>
                                                </h3>

                                                {/* Variant Attributes Chips */}
                                                {item.attributes && Object.keys(item.attributes).length > 0 && (
                                                    <div className="flex flex-wrap gap-1">
                                                        {Object.entries(item.attributes).map(([k, v]) => (
                                                            <span
                                                                key={k}
                                                                className="text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-md font-semibold"
                                                            >
                                                                {k}: {v}
                                                            </span>
                                                        ))}
                                                    </div>
                                                )}

                                                <div className="flex items-baseline gap-2 pt-0.5">
                                                    <span className="text-xs font-semibold text-gray-500">
                                                        Unitario: {formatPrice(item.price)}
                                                    </span>
                                                    {item.originalPrice && item.originalPrice > item.price && (
                                                        <span className="text-[11px] line-through text-gray-400">
                                                            {formatPrice(item.originalPrice)}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Quantity Selector + Item Subtotal + Trash */}
                                        <div className="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto pt-2 sm:pt-0 border-t sm:border-0 dark:border-gray-800">
                                            {/* Quantity Control */}
                                            <div className="flex items-center border dark:border-gray-700 rounded-lg p-0.5 bg-gray-50 dark:bg-gray-800">
                                                <button
                                                    type="button"
                                                    onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                                    disabled={item.quantity <= 1}
                                                    className="p-1 text-gray-500 hover:text-gray-900 dark:hover:text-white disabled:opacity-30"
                                                >
                                                    <HiMinus className="w-3.5 h-3.5" />
                                                </button>
                                                <span className="px-3 text-xs font-bold text-gray-900 dark:text-white">
                                                    {item.quantity}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                                    disabled={item.maxStock !== undefined && item.quantity >= item.maxStock}
                                                    className="p-1 text-gray-500 hover:text-gray-900 dark:hover:text-white disabled:opacity-30"
                                                >
                                                    <HiPlus className="w-3.5 h-3.5" />
                                                </button>
                                            </div>

                                            {/* Total Line Price */}
                                            <span className="text-sm sm:text-base font-extrabold text-gray-900 dark:text-white min-w-[70px] text-right">
                                                {formatPrice(item.price * item.quantity)}
                                            </span>

                                            {/* Delete Button */}
                                            <button
                                                type="button"
                                                onClick={() => removeItem(item.id)}
                                                className="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                                                title="Eliminar producto"
                                            >
                                                <HiTrash className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Continue Shopping Link */}
                            <div className="flex justify-start">
                                <a
                                    href="/catalog"
                                    className="inline-flex items-center gap-2 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline"
                                >
                                    <HiArrowLeft className="w-4 h-4" />
                                    Continuar Comprando
                                </a>
                            </div>
                        </div>

                        {/* Right Column: Order Summary (Sticky) */}
                        <div className="space-y-4 lg:sticky lg:top-28">
                            <Card className="shadow-sm rounded-2xl">
                                <h3 className="text-lg font-bold text-gray-900 dark:text-white pb-3 border-b dark:border-gray-800">
                                    Resumen del Pedido
                                </h3>

                                {/* Coupon Input */}
                                <div className="space-y-2 py-2">
                                    <Label htmlFor="coupon_input" className="text-xs font-bold uppercase text-gray-500">
                                        ¿Tienes un Cupón de Descuento?
                                    </Label>
                                    <form onSubmit={handleApplyCoupon} className="flex gap-2">
                                        <TextInput
                                            id="coupon_input"
                                            icon={HiOutlineTicket}
                                            placeholder="Ej. BIENVENIDA10"
                                            value={couponInput}
                                            onChange={(e) => setCouponInput(e.target.value)}
                                            sizing="sm"
                                            className="flex-1 uppercase font-semibold"
                                            disabled={Boolean(coupon) || isApplyingCoupon}
                                        />
                                        <Button
                                            color="dark"
                                            size="sm"
                                            type="submit"
                                            disabled={!couponInput.trim() || Boolean(coupon) || isApplyingCoupon}
                                        >
                                            {isApplyingCoupon ? <Spinner size="sm" /> : 'Aplicar'}
                                        </Button>
                                    </form>

                                    {/* Coupon Status Feedback */}
                                    {couponMessage && (
                                        <Alert
                                            color={couponMessage.type === 'success' ? 'success' : 'failure'}
                                            className="text-xs py-2 px-3"
                                        >
                                            {couponMessage.text}
                                        </Alert>
                                    )}

                                    {/* Active Coupon Badge */}
                                    {coupon && (
                                        <div className="flex items-center justify-between p-2.5 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-xs">
                                            <div className="flex items-center gap-1.5 text-green-800 dark:text-green-300 font-bold">
                                                <HiTag className="w-4 h-4" />
                                                <span>{coupon.code}</span>
                                                <span className="text-[11px] font-normal opacity-80">
                                                    (-{formatPrice(discountAmount)})
                                                </span>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={removeCoupon}
                                                className="text-red-600 dark:text-red-400 hover:underline text-xs font-bold"
                                            >
                                                Quitar
                                            </button>
                                        </div>
                                    )}
                                </div>

                                {/* Calculation Breakdown */}
                                <div className="space-y-2.5 text-xs text-gray-600 dark:text-gray-300 pt-3 border-t dark:border-gray-800">
                                    <div className="flex justify-between">
                                        <span>Subtotal ({items.length} artículos):</span>
                                        <span className="font-semibold text-gray-900 dark:text-white">
                                            {formatPrice(subtotal)}
                                        </span>
                                    </div>

                                    {discountAmount > 0 && (
                                        <div className="flex justify-between text-green-600 dark:text-green-400 font-semibold">
                                            <span>Descuento aplicado ({coupon?.code}):</span>
                                            <span>-{formatPrice(discountAmount)}</span>
                                        </div>
                                    )}

                                    <div className="flex justify-between">
                                        <span>Envío estimado:</span>
                                        <span className="text-gray-400 italic">Calculado en checkout</span>
                                    </div>

                                    {/* Final Total */}
                                    <div className="flex justify-between items-baseline pt-3 border-t dark:border-gray-800 text-base">
                                        <span className="font-bold text-gray-900 dark:text-white">Total a Pagar:</span>
                                        <span className="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                                            {formatPrice(total)}
                                        </span>
                                    </div>
                                </div>

                                {/* Checkout CTA */}
                                <Button
                                    color="blue"
                                    size="lg"
                                    className="w-full mt-4 shadow-lg shadow-blue-500/20"
                                    onClick={() => (window.location.href = '/checkout')}
                                >
                                    <span className="flex items-center justify-center gap-2 font-bold">
                                        Proceder al Pago Seguro
                                        <HiArrowRight className="w-5 h-5" />
                                    </span>
                                </Button>

                                {/* Trust Guarantee Badges */}
                                <div className="space-y-2 pt-4 border-t dark:border-gray-800 text-[11px] text-gray-500">
                                    <div className="flex items-center gap-2">
                                        <HiLockClosed className="w-4 h-4 text-green-500 flex-shrink-0" />
                                        <span>Transacción encriptada y protegida con SSL</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <HiTruck className="w-4 h-4 text-blue-500 flex-shrink-0" />
                                        <span>Despacho a todo el país con código de seguimiento</span>
                                    </div>
                                </div>
                            </Card>
                        </div>
                    </div>
                )}

                {/* Recommended Products */}
                {recommended_products.length > 0 && (
                    <section className="space-y-6 pt-12 border-t dark:border-gray-800">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                            Productos que podrían interesarte
                        </h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                            {recommended_products.map((p) => (
                                <ProductCard key={p.id} product={p} />
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

export default function TenantCartPage(props: StorefrontCartPageProps) {
    return (
        <StorefrontLayout
            domain={props.domain}
            title="Mi Carrito de Compras"
            storeSettings={props.store_settings}
            categories={props.categories}
            authUser={props.auth_user}
        >
            <CartPageContent {...props} />
        </StorefrontLayout>
    );
}
