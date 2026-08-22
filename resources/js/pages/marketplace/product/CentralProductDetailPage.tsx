import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CentralLayout from '@/components/layouts/CentralLayout';
import { useCentralCart } from '@/contexts/CentralCartContext';
import CentralMarketplaceServices, {
    CentralProductItem,
    TenantStoreItem,
} from '@/Services/CentralMarketplaceServices';
import {
    HiOutlineShoppingBag,
    HiOutlineBuildingStorefront,
    HiOutlineCheckCircle,
    HiOutlineArrowTopRightOnSquare,
    HiOutlineShieldCheck,
    HiOutlineTruck,
    HiOutlineArrowLeft,
} from 'react-icons/hi2';
import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';

interface CentralProductDetailPageProps {
    domain?: string;
    slug: string;
    product_initial?: CentralProductItem;
    store?: TenantStoreItem;
}

const CentralProductDetailPageContent: React.FC<CentralProductDetailPageProps> = ({
    domain,
    slug,
    product_initial,
    store: storeInitial,
}) => {
    const { addItem } = useCentralCart();
    const [product, setProduct] = useState<CentralProductItem | null>(product_initial || null);
    const [store, setStore] = useState<TenantStoreItem | null>(storeInitial || null);
    const [related, setRelated] = useState<CentralProductItem[]>([]);
    const [loading, setLoading] = useState(!product_initial);

    const [selectedImage, setSelectedImage] = useState<string | null>(null);
    const [quantity, setQuantity] = useState(1);
    const [selectedVariants, setSelectedVariants] = useState<Record<string, string>>({});

    useEffect(() => {
        if (!product_initial) {
            setLoading(true);
            CentralMarketplaceServices.getProductDetail(slug).then(res => {
                if ((res.code === 200 || res.status === 'success') && res.data) {
                    setProduct(res.data.product);
                    setStore(res.data.store);
                    setRelated(res.data.related || []);
                    if (res.data.product.images && res.data.product.images.length > 0) {
                        setSelectedImage(res.data.product.images[0].image_path);
                    }
                }
                setLoading(false);
            });
        } else if (product_initial.images && product_initial.images.length > 0) {
            setSelectedImage(product_initial.images[0].image_path);
        }
    }, [slug, product_initial]);

    if (loading || !product) {
        return <div className="py-20 text-center text-gray-500">Cargando producto...</div>;
    }

    const mainImage =
        selectedImage ||
        (product.images && product.images.length > 0
            ? product.images[0].image_path
            : null);

    /**
     * Hallazgo G6: el tope del selector era `Math.min(product.quantity || 99, ...)`. Con
     * `quantity: 0` el `||` convertia el tope en **99**, asi que el marketplace central
     * dejaba anadir 99 unidades de un producto agotado. Ni el boton ni `handleAddToCart`
     * comprobaban el stock. La ficha del storefront de tienda si lo hacia: era una
     * regresion de la pagina nueva.
     */
    const availableStock = Number.isFinite(Number(product.quantity)) ? Number(product.quantity) : 0;
    const isOutOfStock = availableStock <= 0;
    const maxQuantity = Math.max(1, availableStock);

    const handleAddToCart = () => {
        if (isOutOfStock) return;

        addItem({
            tenant_id: product.tenant_id,
            tenant_name: store?.name || product.tenant_name || 'Tienda Asociada',
            tenant_domain: store?.domain || product.tenant_domain,
            product_id: product.tenant_product_id || product.id,
            product_name: product.name,
            slug: product.slug,
            price: product.price,
            quantity,
            image: mainImage,
            sku: product.sku,
            attributes: selectedVariants,
        });
    };

    const handleBuyNow = () => {
        if (isOutOfStock) return;

        handleAddToCart();
        window.location.href = '/checkout';
    };

    return (
        <>
            <Head title={`${product.name} - OwOMarket Central`} />

            <div className="space-y-12">
                {/* Back to Catalog */}
                <div>
                    <Link
                        href="/marketplace"
                        className="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 transition"
                    >
                        <HiOutlineArrowLeft className="w-4 h-4" />
                        Volver al Catálogo del Marketplace
                    </Link>
                </div>

                {/* Main Product Section */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
                    {/* Left Images Column */}
                    <div className="lg:col-span-6 space-y-4">
                        <div className="aspect-square rounded-3xl overflow-hidden bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-center">
                            {mainImage ? (
                                <img
                                    src={mainImage}
                                    alt={product.name}
                                    className="w-full h-full object-cover"
                                />
                            ) : (
                                <div className="text-gray-400 font-bold text-xl">OwOMarket</div>
                            )}
                        </div>

                        {/* Image Gallery Thumbnails */}
                        {product.images && product.images.length > 1 && (
                            <div className="flex items-center gap-3 overflow-x-auto pb-2">
                                {product.images.map((img, idx) => (
                                    <button
                                        key={idx}
                                        onClick={() => setSelectedImage(img.image_path)}
                                        className={`w-16 h-16 rounded-xl overflow-hidden border-2 flex-shrink-0 transition ${
                                            mainImage === img.image_path
                                                ? 'border-blue-600 ring-2 ring-blue-500/30'
                                                : 'border-gray-200 dark:border-gray-700 opacity-70 hover:opacity-100'
                                        }`}
                                    >
                                        <img
                                            src={img.image_path}
                                            alt={product.name}
                                            className="w-full h-full object-cover"
                                        />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Right Details Column */}
                    <div className="lg:col-span-6 space-y-6">
                        {/* Store Info Card */}
                        {store && (
                            <div className="p-4 rounded-2xl bg-gradient-to-r from-blue-50/60 to-purple-50/60 dark:from-gray-900 dark:to-gray-900 border border-blue-100 dark:border-gray-800 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-blue-600 text-white font-bold flex items-center justify-center">
                                        <HiOutlineBuildingStorefront className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <span className="text-[10px] uppercase tracking-wider text-gray-500 font-bold">Vendido y enviado por</span>
                                        <h4 className="text-sm font-black text-gray-900 dark:text-white flex items-center gap-1.5">
                                            {store.name}
                                            <HiOutlineCheckCircle className="w-4 h-4 text-green-500" />
                                        </h4>
                                    </div>
                                </div>

                                {store.domain && (
                                    <a
                                        href={`http://${store.domain}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs font-bold text-blue-600 dark:text-blue-400 hover:shadow-sm transition"
                                    >
                                        Visitar Tienda
                                        <HiOutlineArrowTopRightOnSquare className="w-3.5 h-3.5" />
                                    </a>
                                )}
                            </div>
                        )}

                        {/* Title & Category */}
                        <div className="space-y-2">
                            {product.category_name && (
                                <span className="px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                    {product.category_name}
                                </span>
                            )}
                            <h1 className="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white leading-tight">
                                {product.name}
                            </h1>
                            {product.sku && (
                                <p className="text-xs text-gray-400">SKU: {product.sku}</p>
                            )}
                        </div>

                        {/* Price Display */}
                        <div className="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <div>
                                <span className="text-xs text-gray-500 dark:text-gray-400 block font-medium">
                                    Precio del Producto
                                </span>
                                <CurrencyPriceDisplay
                                    priceUsd={product.price}
                                    comparePriceUsd={product.compare_price ?? undefined}
                                    size="xl"
                                    showVes={true}
                                    showUsdt={true}
                                    showBcvLabel={true}
                                />
                            </div>
                            <div className="text-right">
                                {isOutOfStock ? (
                                    <span className="text-xs text-red-600 dark:text-red-400 font-bold block">
                                        Agotado
                                    </span>
                                ) : (
                                    <span className="text-xs text-green-600 dark:text-green-400 font-bold block">
                                        ✓ Stock Disponible: {availableStock}
                                    </span>
                                )}
                                <span className="text-[11px] text-gray-400">Entrega en todo el país</span>
                            </div>
                        </div>

                        {/* Description */}
                        {product.description && (
                            <div className="space-y-2">
                                <h3 className="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                    Descripción del Producto
                                </h3>
                                <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                                    {product.description}
                                </p>
                            </div>
                        )}

                        {/* Quantity Selector & Action Buttons */}
                        <div className="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                            <div className="flex items-center gap-4">
                                <label className="text-xs font-bold text-gray-700 dark:text-gray-300">Cantidad:</label>
                                <div className="flex items-center border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900">
                                    <button
                                        type="button"
                                        onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                        disabled={quantity <= 1 || isOutOfStock}
                                        className="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-l-xl disabled:opacity-40 disabled:cursor-not-allowed"
                                    >
                                        -
                                    </button>
                                    <span className="px-4 text-sm font-bold text-gray-900 dark:text-white">
                                        {quantity}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => setQuantity(Math.min(maxQuantity, quantity + 1))}
                                        disabled={quantity >= maxQuantity || isOutOfStock}
                                        className="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-r-xl disabled:opacity-40 disabled:cursor-not-allowed"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                <button
                                    type="button"
                                    onClick={handleAddToCart}
                                    disabled={isOutOfStock}
                                    className="w-full py-3.5 px-6 rounded-2xl border-2 border-blue-600 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 text-sm font-bold flex items-center justify-center gap-2 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    <HiOutlineShoppingBag className="w-5 h-5" />
                                    Añadir al Carrito
                                </button>
                                <button
                                    type="button"
                                    onClick={handleBuyNow}
                                    disabled={isOutOfStock}
                                    className="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-bold shadow-lg shadow-blue-500/20 transition flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    Comprar Ahora
                                </button>
                            </div>
                        </div>

                        {/* Security Guarantees */}
                        <div className="grid grid-cols-2 gap-3 pt-4">
                            <div className="p-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                <HiOutlineShieldCheck className="w-5 h-5 text-green-500 flex-shrink-0" />
                                <span>Compra Segura y Verificada</span>
                            </div>
                            <div className="p-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                <HiOutlineTruck className="w-5 h-5 text-blue-500 flex-shrink-0" />
                                <span>Envío directo por la tienda</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Related Products Section */}
                {related.length > 0 && (
                    <div className="space-y-6 pt-12 border-t border-gray-200 dark:border-gray-800">
                        <h2 className="text-xl font-black text-gray-900 dark:text-white">
                            Productos Relacionados
                        </h2>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            {related.map(rel => (
                                <Link
                                    key={rel.id}
                                    href={`/product/${rel.id}`}
                                    className="group rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3 space-y-2 hover:shadow-lg transition"
                                >
                                    <div className="aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800">
                                        {rel.images && rel.images.length > 0 ? (
                                            <img
                                                src={rel.images[0].image_path}
                                                alt={rel.name}
                                                className="w-full h-full object-cover group-hover:scale-105 transition"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center text-gray-400 text-xs">
                                                OwO
                                            </div>
                                        )}
                                    </div>
                                    <h4 className="text-xs font-bold text-gray-900 dark:text-white truncate">
                                        {rel.name}
                                    </h4>
                                    <p className="text-xs font-black text-blue-600 dark:text-blue-400">
                                        ${rel.price.toFixed(2)}
                                    </p>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
};

const CentralProductDetailPage: React.FC<CentralProductDetailPageProps> = (props) => {
    return (
        <CentralLayout>
            <CentralProductDetailPageContent {...props} />
        </CentralLayout>
    );
};

export default CentralProductDetailPage;
