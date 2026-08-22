import CentralLayout from '@/components/layouts/CentralLayout';
import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';
import { useCentralCart } from '@/contexts/CentralCartContext';
import CentralMarketplaceServices, { CentralProductItem, TenantStoreItem } from '@/Services/CentralMarketplaceServices';
import { Head, Link } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import {
    HiOutlineArrowLeft,
    HiOutlineArrowTopRightOnSquare,
    HiOutlineBuildingStorefront,
    HiOutlineCheckCircle,
    HiOutlineShieldCheck,
    HiOutlineShoppingBag,
    HiOutlineTruck,
} from 'react-icons/hi2';

interface CentralProductDetailPageProps {
    domain?: string;
    slug: string;
    product_initial?: CentralProductItem;
    store?: TenantStoreItem;
}

const CentralProductDetailPageContent: React.FC<CentralProductDetailPageProps> = ({ domain, slug, product_initial, store: storeInitial }) => {
    const { addItem } = useCentralCart();
    const [product, setProduct] = useState<CentralProductItem | null>(product_initial || null);
    const [store, setStore] = useState<TenantStoreItem | null>(storeInitial || null);
    const [related, setRelated] = useState<CentralProductItem[]>([]);
    const [loading, setLoading] = useState(!product_initial);

    const [selectedImage, setSelectedImage] = useState<string | null>(null);
    const [quantity, setQuantity] = useState(1);

    /*
    | Hallazgo N36: esta ficha no ofrecia variantes. El comprador no podia elegir talla ni
    | color, el comerciante recibia un pedido sin saber que enviar, y el stock se
    | descontaba del producto padre —un numero que nadie mantiene cuando hay variantes—.
    |
    | Se preselecciona la primera, igual que hace la ficha del storefront de tienda, para
    | que el comportamiento sea el mismo en los dos sitios.
    */
    const [selectedVariantId, setSelectedVariantId] = useState<string | null>(null);

    useEffect(() => {
        const variantes = product?.variants ?? [];
        setSelectedVariantId(variantes.length > 0 ? variantes[0].id : null);
    }, [product]);

    useEffect(() => {
        if (!product_initial) {
            setLoading(true);
            CentralMarketplaceServices.getProductDetail(slug)
                .then((res) => {
                    if ((res.code === 200 || res.status === 'success') && res.data) {
                        setProduct(res.data.product);
                        setStore(res.data.store);
                        setRelated(res.data.related || []);
                        if (res.data.product.images && res.data.product.images.length > 0) {
                            setSelectedImage(res.data.product.images[0].image_path);
                        }
                    }
                })
                // `finally` y no dentro del `then`: si la promesa se rechazara, la pagina
                // se quedaria cargando para siempre, que es justo el hallazgo G15.
                .finally(() => setLoading(false));
        } else if (product_initial.images && product_initial.images.length > 0) {
            setSelectedImage(product_initial.images[0].image_path);
        }
    }, [slug, product_initial]);

    // Hallazgo G15: esto se quedaba en «Cargando producto...» PARA SIEMPRE si la peticion
    // fallaba, porque `loading` solo se apagaba en la rama de exito.
    if (loading) {
        return <div className="py-20 text-center text-gray-500">Cargando producto…</div>;
    }

    if (!product) {
        return (
            <div className="space-y-3 py-20 text-center">
                <p className="font-bold text-gray-900 dark:text-white">No pudimos cargar este producto.</p>
                <p className="text-xs text-gray-500">Puede que ya no esté disponible en el marketplace.</p>
                <a href="/catalog" className="inline-block text-xs font-bold text-blue-600 hover:underline">
                    Volver al catálogo
                </a>
            </div>
        );
    }

    const mainImage = selectedImage || (product.images && product.images.length > 0 ? product.images[0].image_path : null);

    /**
     * Hallazgo G6: el tope del selector era `Math.min(product.quantity || 99, ...)`. Con
     * `quantity: 0` el `||` convertia el tope en **99**, asi que el marketplace central
     * dejaba anadir 99 unidades de un producto agotado. Ni el boton ni `handleAddToCart`
     * comprobaban el stock. La ficha del storefront de tienda si lo hacia: era una
     * regresion de la pagina nueva.
     */
    const variantes = product.variants ?? [];
    const varianteActiva = variantes.find((v) => v.id === selectedVariantId) ?? null;

    /*
    | El precio y el stock salen de la variante cuando la hay. El `quantity` del padre no
    | sirve en un producto con variantes: `StockReserver` sólo descuenta de la variante, así
    | que ese número se queda como estaba el día de la siembra (hallazgo N36).
    */
    const precioActivo = varianteActiva?.price ?? product.price;
    const stockDeclarado = varianteActiva ? varianteActiva.quantity : Number(product.quantity);
    const availableStock = Number.isFinite(Number(stockDeclarado)) ? Number(stockDeclarado) : 0;
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
            variant_id: varianteActiva?.id ?? null,
            price: precioActivo,
            quantity,
            image: mainImage,
            sku: varianteActiva?.sku ?? product.sku,
            attributes: varianteActiva?.attributes ?? {},
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
                        className="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 transition hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                    >
                        <HiOutlineArrowLeft className="h-4 w-4" />
                        Volver al Catálogo del Marketplace
                    </Link>
                </div>

                {/* Main Product Section */}
                <div className="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-12">
                    {/* Left Images Column */}
                    <div className="space-y-4 lg:col-span-6">
                        <div className="flex aspect-square items-center justify-center overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            {mainImage ? (
                                <img src={mainImage} alt={product.name} className="h-full w-full object-cover" />
                            ) : (
                                <div className="text-xl font-bold text-gray-400">OwOMarket</div>
                            )}
                        </div>

                        {/* Image Gallery Thumbnails */}
                        {product.images && product.images.length > 1 && (
                            <div className="flex items-center gap-3 overflow-x-auto pb-2">
                                {product.images.map((img, idx) => (
                                    <button
                                        key={idx}
                                        onClick={() => setSelectedImage(img.image_path)}
                                        className={`h-16 w-16 flex-shrink-0 overflow-hidden rounded-xl border-2 transition ${
                                            mainImage === img.image_path
                                                ? 'border-blue-600 ring-2 ring-blue-500/30'
                                                : 'border-gray-200 opacity-70 hover:opacity-100 dark:border-gray-700'
                                        }`}
                                    >
                                        <img src={img.image_path} alt={product.name} className="h-full w-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Right Details Column */}
                    <div className="space-y-6 lg:col-span-6">
                        {/* Store Info Card */}
                        {store && (
                            <div className="flex items-center justify-between rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50/60 to-purple-50/60 p-4 dark:border-gray-800 dark:from-gray-900 dark:to-gray-900">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 font-bold text-white">
                                        <HiOutlineBuildingStorefront className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <span className="text-[10px] font-bold tracking-wider text-gray-500 uppercase">Vendido y enviado por</span>
                                        <h4 className="flex items-center gap-1.5 text-sm font-black text-gray-900 dark:text-white">
                                            {store.name}
                                            <HiOutlineCheckCircle className="h-4 w-4 text-green-500" />
                                        </h4>
                                    </div>
                                </div>

                                {store.domain && (
                                    <a
                                        href={`http://${store.domain}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-blue-600 transition hover:shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-blue-400"
                                    >
                                        Visitar Tienda
                                        <HiOutlineArrowTopRightOnSquare className="h-3.5 w-3.5" />
                                    </a>
                                )}
                            </div>
                        )}

                        {/* Title & Category */}
                        <div className="space-y-2">
                            {product.category_name && (
                                <span className="rounded-md bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                    {product.category_name}
                                </span>
                            )}
                            <h1 className="text-2xl leading-tight font-black text-gray-900 sm:text-3xl dark:text-white">{product.name}</h1>
                            {product.sku && <p className="text-xs text-gray-400">SKU: {product.sku}</p>}
                        </div>

                        {/* Price Display */}
                        <div className="flex items-center justify-between rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div>
                                <span className="block text-xs font-medium text-gray-500 dark:text-gray-400">Precio del Producto</span>
                                <CurrencyPriceDisplay
                                    priceUsd={precioActivo}
                                    comparePriceUsd={product.compare_price ?? undefined}
                                    size="xl"
                                    showVes={true}
                                    showUsdt={true}
                                    showBcvLabel={true}
                                />
                            </div>
                            <div className="text-right">
                                {isOutOfStock ? (
                                    <span className="block text-xs font-bold text-red-600 dark:text-red-400">Agotado</span>
                                ) : (
                                    <span className="block text-xs font-bold text-green-600 dark:text-green-400">
                                        ✓ Stock Disponible: {availableStock}
                                    </span>
                                )}
                                <span className="text-[11px] text-gray-400">Entrega en todo el país</span>
                            </div>
                        </div>

                        {/* Description */}
                        {product.description && (
                            <div className="space-y-2">
                                <h3 className="text-xs font-bold tracking-wider text-gray-900 uppercase dark:text-white">Descripción del Producto</h3>
                                <p className="text-xs leading-relaxed whitespace-pre-line text-gray-600 sm:text-sm dark:text-gray-300">
                                    {product.description}
                                </p>
                            </div>
                        )}

                        {/* Hallazgo N36: selector de variante. Mismo comportamiento que la ficha
                            del storefront de tienda, para que comprar la misma camiseta por el
                            marketplace o por la tienda no se sienta distinto. */}
                        {variantes.length > 0 && (
                            <div className="space-y-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                                <label className="text-xs font-bold tracking-wider text-gray-500 uppercase">Opciones disponibles:</label>
                                <div className="flex flex-wrap gap-2">
                                    {variantes.map((variante) => {
                                        const elegida = selectedVariantId === variante.id;
                                        const agotada = variante.quantity <= 0;
                                        const etiqueta =
                                            Object.entries(variante.attributes || {})
                                                .map(([clave, valor]) => `${clave}: ${valor}`)
                                                .join(' / ') || `Opción ${variante.sku || ''}`;

                                        return (
                                            <button
                                                key={variante.id}
                                                type="button"
                                                disabled={agotada}
                                                onClick={() => {
                                                    setSelectedVariantId(variante.id);
                                                    setQuantity(1);
                                                }}
                                                className={`flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-bold transition-all ${
                                                    agotada
                                                        ? 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 line-through dark:border-gray-800 dark:bg-gray-800 dark:text-gray-600'
                                                        : elegida
                                                          ? 'border-blue-600 bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                                          : 'border-gray-200 bg-white text-gray-800 hover:border-blue-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200'
                                                }`}
                                            >
                                                <span>{etiqueta}</span>
                                                {!agotada && <span className="text-[11px] opacity-80">(${variante.price.toFixed(2)})</span>}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {/* Quantity Selector & Action Buttons */}
                        <div className="space-y-4 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <div className="flex items-center gap-4">
                                <label className="text-xs font-bold text-gray-700 dark:text-gray-300">Cantidad:</label>
                                <div className="flex items-center rounded-xl border border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900">
                                    <button
                                        type="button"
                                        onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                        disabled={quantity <= 1 || isOutOfStock}
                                        className="rounded-l-xl px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-300 dark:hover:bg-gray-800"
                                    >
                                        -
                                    </button>
                                    <span className="px-4 text-sm font-bold text-gray-900 dark:text-white">{quantity}</span>
                                    <button
                                        type="button"
                                        onClick={() => setQuantity(Math.min(maxQuantity, quantity + 1))}
                                        disabled={quantity >= maxQuantity || isOutOfStock}
                                        className="rounded-r-xl px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-300 dark:hover:bg-gray-800"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-3 pt-2 sm:grid-cols-2">
                                <button
                                    type="button"
                                    onClick={handleAddToCart}
                                    disabled={isOutOfStock}
                                    className="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-blue-600 px-6 py-3.5 text-sm font-bold text-blue-600 transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-40 dark:text-blue-400 dark:hover:bg-blue-900/30"
                                >
                                    <HiOutlineShoppingBag className="h-5 w-5" />
                                    Añadir al Carrito
                                </button>
                                <button
                                    type="button"
                                    onClick={handleBuyNow}
                                    disabled={isOutOfStock}
                                    className="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition hover:from-blue-700 hover:to-indigo-700 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Comprar Ahora
                                </button>
                            </div>
                        </div>

                        {/* Security Guarantees */}
                        <div className="grid grid-cols-2 gap-3 pt-4">
                            <div className="flex items-center gap-2 rounded-xl border border-gray-100 bg-gray-50 p-3 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                                <HiOutlineShieldCheck className="h-5 w-5 flex-shrink-0 text-green-500" />
                                <span>Compra Segura y Verificada</span>
                            </div>
                            <div className="flex items-center gap-2 rounded-xl border border-gray-100 bg-gray-50 p-3 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                                <HiOutlineTruck className="h-5 w-5 flex-shrink-0 text-blue-500" />
                                <span>Envío directo por la tienda</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Related Products Section */}
                {related.length > 0 && (
                    <div className="space-y-6 border-t border-gray-200 pt-12 dark:border-gray-800">
                        <h2 className="text-xl font-black text-gray-900 dark:text-white">Productos Relacionados</h2>
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            {related.map((rel) => (
                                <Link
                                    key={rel.id}
                                    href={`/product/${rel.id}`}
                                    className="group space-y-2 rounded-2xl border border-gray-200 bg-white p-3 transition hover:shadow-lg dark:border-gray-800 dark:bg-gray-900"
                                >
                                    <div className="aspect-square overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                                        {rel.images && rel.images.length > 0 ? (
                                            <img
                                                src={rel.images[0].image_path}
                                                alt={rel.name}
                                                className="h-full w-full object-cover transition group-hover:scale-105"
                                            />
                                        ) : (
                                            <div className="flex h-full w-full items-center justify-center text-xs text-gray-400">OwO</div>
                                        )}
                                    </div>
                                    <h4 className="truncate text-xs font-bold text-gray-900 dark:text-white">{rel.name}</h4>
                                    <p className="text-xs font-black text-blue-600 dark:text-blue-400">${rel.price.toFixed(2)}</p>
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
