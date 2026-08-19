import React, { useMemo, useState } from 'react';
import StorefrontLayout from '@/components/layouts/StorefrontLayout';
import ProductCard from '@/components/ui/storefront/ProductCard';
import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';
import { useCart } from '@/contexts/CartContext';
import ReviewServices from '@/Services/ReviewServices';
import { StorefrontProductDetailPageProps, StorefrontProductVariant } from '@/types/models/Storefront';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Label,
    Modal,
    ModalBody,
    ModalHeader,
    Progress,
    Spinner,
    TabItem,
    Tabs,
    TextInput,
    Textarea,
} from 'flowbite-react';
import {
    HiArrowRight,
    HiCheck,
    HiCheckCircle,
    HiHeart,
    HiHome,
    HiLockClosed,
    HiMinus,
    HiOutlineShoppingBag,
    HiOutlineSparkles,
    HiPlus,
    HiShieldCheck,
    HiStar,
    HiTruck,
} from 'react-icons/hi';
import { FaReply } from 'react-icons/fa';

function ProductDetailPageContent({
    domain,
    store_settings,
    categories = [],
    product,
    reviews = [],
    reviews_summary,
    related_products = [],
    auth_user = null,
}: StorefrontProductDetailPageProps) {
    const { addItem, formatPrice } = useCart();

    // Image gallery active index
    const [activeImageIndex, setActiveImageIndex] = useState<number>(0);

    // Selected variant
    const [selectedVariantId, setSelectedVariantId] = useState<string | null>(
        product.variants.length > 0 ? product.variants[0].id : null
    );

    // Selected quantity
    const [quantity, setQuantity] = useState<number>(1);

    // Review modal & form state
    const [isReviewModalOpen, setIsReviewModalOpen] = useState<boolean>(false);
    const [reviewRating, setReviewRating] = useState<number>(5);
    const [hoverRating, setHoverRating] = useState<number>(0);
    const [reviewTitle, setReviewTitle] = useState<string>('');
    const [reviewComment, setReviewComment] = useState<string>('');
    const [authorName, setAuthorName] = useState<string>(auth_user?.name || '');
    const [authorEmail, setAuthorEmail] = useState<string>(auth_user?.email || '');
    const [isSubmittingReview, setIsSubmittingReview] = useState<boolean>(false);
    const [toastMessage, setToastMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const showToast = (type: 'success' | 'error', text: string) => {
        setToastMessage({ type, text });
        setTimeout(() => setToastMessage(null), 5000);
    };

    // Active variant resolution
    const activeVariant = useMemo<StorefrontProductVariant | null>(() => {
        if (!selectedVariantId) return null;
        return product.variants.find((v) => v.id === selectedVariantId) || null;
    }, [selectedVariantId, product.variants]);

    // Active pricing and stock
    const currentPrice = activeVariant ? activeVariant.price : product.price;
    const currentComparePrice = activeVariant ? activeVariant.compare_price : product.compare_price;
    const currentStock = activeVariant ? activeVariant.quantity : product.quantity;
    const currentSku = activeVariant?.sku || product.sku;

    const discountPercentage =
        currentComparePrice && currentComparePrice > currentPrice
            ? Math.round(((currentComparePrice - currentPrice) / currentComparePrice) * 100)
            : 0;

    const isOutOfStock = currentStock <= 0;

    // Gallery images (merging product images + variant image if available)
    const galleryImages = useMemo(() => {
        const list = [...product.images];
        if (activeVariant?.image && !list.includes(activeVariant.image)) {
            return [activeVariant.image, ...list];
        }
        return list;
    }, [product.images, activeVariant]);

    const activeImageUrl = galleryImages[activeImageIndex] || galleryImages[0] || '';

    // Handle Add to Cart
    const handleAddToCart = (redirectCheckout = false) => {
        if (isOutOfStock) return;

        addItem({
            productId: product.id,
            variantId: activeVariant?.id,
            name: product.name,
            slug: product.slug,
            sku: currentSku,
            image: activeImageUrl || undefined,
            price: currentPrice,
            originalPrice: currentComparePrice || undefined,
            quantity: quantity,
            maxStock: currentStock,
            attributes: activeVariant?.attributes,
        });

        if (redirectCheckout) {
            window.location.href = '/checkout';
        }
    };

    // Handle Review Submit
    const handleReviewSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (reviewComment.trim().length < 5) {
            showToast('error', 'El comentario debe tener al menos 5 caracteres.');
            return;
        }

        setIsSubmittingReview(true);
        try {
            const payload = {
                product_id: product.id,
                rating: reviewRating,
                title: reviewTitle.trim() !== '' ? reviewTitle.trim() : undefined,
                comment: reviewComment.trim(),
                author_name: authorName.trim() !== '' ? authorName.trim() : undefined,
                email: authorEmail.trim() !== '' ? authorEmail.trim() : undefined,
            };

            const res = await ReviewServices.create(payload as any);
            if (res.data && (res.data.code === 200 || res.data.status === 'success')) {
                showToast(
                    'success',
                    '¡Gracias por tu opinión! Tu reseña ha sido enviada para moderación y aprobación.'
                );
                setIsReviewModalOpen(false);
                setReviewComment('');
                setReviewTitle('');
            } else {
                showToast('error', res.data?.message || 'Error al enviar reseña.');
            }
        } catch {
            showToast('error', 'Error al comunicarse con el servidor.');
        } finally {
            setIsSubmittingReview(false);
        }
    };

    return (
        <>
            <div className="space-y-12">
                {/* Toast Notification */}
                {toastMessage && (
                    <div
                        className={`fixed top-6 right-6 z-50 flex items-center p-4 text-sm rounded-xl shadow-2xl ${
                            toastMessage.type === 'success'
                                ? 'text-green-800 bg-green-100 dark:bg-green-800 dark:text-green-200 border border-green-300'
                                : 'text-red-800 bg-red-100 dark:bg-red-800 dark:text-red-200 border border-red-300'
                        }`}
                    >
                        <span className="font-bold mr-2">
                            {toastMessage.type === 'success' ? 'Éxito:' : 'Aviso:'}
                        </span>
                        {toastMessage.text}
                    </div>
                )}

                {/* Breadcrumb */}
                <Breadcrumb>
                    <BreadcrumbItem href="/" icon={HiHome}>
                        Inicio
                    </BreadcrumbItem>
                    <BreadcrumbItem href="/catalog">Catálogo</BreadcrumbItem>
                    {product.category_name && (
                        <BreadcrumbItem href={`/catalog?category=${product.category_slug}`}>
                            {product.category_name}
                        </BreadcrumbItem>
                    )}
                    <BreadcrumbItem className="truncate max-w-xs">{product.name}</BreadcrumbItem>
                </Breadcrumb>

                {/* Main Product Showcase (2 Columns) */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-start">
                    {/* Left Column: Image Gallery */}
                    <div className="space-y-4">
                        {/* Main Large Image */}
                        <div className="relative aspect-square w-full bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm flex items-center justify-center">
                            {discountPercentage > 0 && (
                                <span className="absolute top-4 left-4 z-10 bg-red-600 text-white text-xs font-black px-2.5 py-1 rounded-full shadow-md">
                                    -{discountPercentage}%
                                </span>
                            )}

                            {activeImageUrl ? (
                                <img
                                    src={activeImageUrl}
                                    alt={product.name}
                                    className="w-full h-full object-contain p-4 hover:scale-105 transition-transform duration-500"
                                    onError={(e) => {
                                        (e.target as HTMLImageElement).src =
                                            'https://via.placeholder.com/600x600?text=Producto';
                                    }}
                                />
                            ) : (
                                <div className="text-gray-300 dark:text-gray-600 flex flex-col items-center gap-2">
                                    <HiOutlineShoppingBag className="w-20 h-20" />
                                    <span className="text-xs">Sin imagen</span>
                                </div>
                            )}
                        </div>

                        {/* Thumbnail Strip */}
                        {galleryImages.length > 1 && (
                            <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-none">
                                {galleryImages.map((img, idx) => (
                                    <button
                                        key={idx}
                                        type="button"
                                        onClick={() => setActiveImageIndex(idx)}
                                        className={`relative w-20 h-20 flex-shrink-0 rounded-xl overflow-hidden border-2 bg-white dark:bg-gray-900 transition-all ${
                                            activeImageIndex === idx
                                                ? 'border-blue-600 shadow-md scale-105'
                                                : 'border-gray-200 dark:border-gray-800 opacity-70 hover:opacity-100'
                                        }`}
                                    >
                                        <img
                                            src={img}
                                            alt={`Thumbnail ${idx}`}
                                            className="w-full h-full object-contain p-1"
                                        />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Right Column: Details & Purchase Options */}
                    <div className="space-y-6">
                        {/* Brand & Category */}
                        <div className="flex items-center gap-2">
                            {product.brand_name && (
                                <span className="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold px-2.5 py-0.5 rounded-md uppercase tracking-wider">
                                    {product.brand_name}
                                </span>
                            )}
                            {product.category_name && (
                                <span className="text-xs text-blue-600 dark:text-blue-400 font-semibold">
                                    {product.category_name}
                                </span>
                            )}
                        </div>

                        {/* Title */}
                        <h1 className="text-2xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight leading-tight">
                            {product.name}
                        </h1>

                        {/* Rating Stars Summary Link */}
                        <div className="flex items-center gap-3">
                            <div className="flex items-center text-amber-400">
                                {[1, 2, 3, 4, 5].map((star) => (
                                    <HiStar
                                        key={star}
                                        className={`w-4 h-4 ${
                                            star <= Math.round(product.rating || 5)
                                                ? 'text-amber-400'
                                                : 'text-gray-300 dark:text-gray-700'
                                        }`}
                                    />
                                ))}
                            </div>
                            <span className="text-sm font-bold text-gray-700 dark:text-gray-300">
                                {Number(product.rating || 5.0).toFixed(1)}
                            </span>
                            <a
                                href="#reviews-section"
                                className="text-xs text-blue-600 dark:text-blue-400 hover:underline font-semibold"
                            >
                                ({product.reviews_count} {product.reviews_count === 1 ? 'opinión' : 'opiniones'})
                            </a>
                        </div>

                        {/* Price & SKU */}
                        <div className="p-4 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl flex items-baseline justify-between flex-wrap gap-2">
                            <CurrencyPriceDisplay
                                priceUsd={currentPrice}
                                comparePriceUsd={currentComparePrice}
                                size="xl"
                                showVes={true}
                                showUsdt={true}
                                showBcvLabel={true}
                            />
                            {currentSku && (
                                <span className="text-xs text-gray-400 font-mono">
                                    SKU: {currentSku}
                                </span>
                            )}
                        </div>

                        {/* Stock Availability */}
                        <div>
                            {isOutOfStock ? (
                                <div className="inline-flex items-center gap-1.5 px-3 py-1 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg text-xs font-bold">
                                    <span>❌ Agotado temporalmente</span>
                                </div>
                            ) : (
                                <div className="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-xs font-bold">
                                    <HiCheckCircle className="w-4 h-4" />
                                    <span>En Stock ({currentStock} disponibles)</span>
                                </div>
                            )}
                        </div>

                        {/* Variants Selector */}
                        {product.variants.length > 0 && (
                            <div className="space-y-3 pt-2 border-t dark:border-gray-800">
                                <Label className="text-xs font-bold uppercase tracking-wider text-gray-500">
                                    Opciones y Variantes Disponibles:
                                </Label>
                                <div className="flex flex-wrap gap-2">
                                    {product.variants.map((variant) => {
                                        const isSelected = selectedVariantId === variant.id;
                                        const labelText =
                                            Object.entries(variant.attributes)
                                                .map(([k, v]) => `${k}: ${v}`)
                                                .join(' / ') || `Opción ${variant.sku || ''}`;

                                        return (
                                            <button
                                                key={variant.id}
                                                type="button"
                                                onClick={() => {
                                                    setSelectedVariantId(variant.id);
                                                    setQuantity(1);
                                                }}
                                                className={`px-3.5 py-2 rounded-xl text-xs font-bold border transition-all flex items-center gap-2 ${
                                                    isSelected
                                                        ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/20'
                                                        : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:border-blue-400'
                                                }`}
                                            >
                                                <span>{labelText}</span>
                                                <span className="text-[11px] opacity-80">
                                                    ({formatPrice(variant.price)})
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {/* Quantity & Action Buttons */}
                        <div className="space-y-4 pt-4 border-t dark:border-gray-800">
                            <div className="flex items-center gap-4">
                                <div className="flex items-center border dark:border-gray-700 rounded-xl p-1 bg-white dark:bg-gray-800 shadow-sm">
                                    <button
                                        type="button"
                                        onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                                        disabled={quantity <= 1 || isOutOfStock}
                                        className="p-2 text-gray-500 hover:text-gray-900 dark:hover:text-white disabled:opacity-30"
                                    >
                                        <HiMinus className="w-4 h-4" />
                                    </button>
                                    <span className="px-4 text-sm font-bold text-gray-900 dark:text-white">
                                        {quantity}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => setQuantity((q) => Math.min(currentStock, q + 1))}
                                        disabled={quantity >= currentStock || isOutOfStock}
                                        className="p-2 text-gray-500 hover:text-gray-900 dark:hover:text-white disabled:opacity-30"
                                    >
                                        <HiPlus className="w-4 h-4" />
                                    </button>
                                </div>

                                <Button
                                    color="blue"
                                    size="lg"
                                    className="flex-1"
                                    disabled={isOutOfStock}
                                    onClick={() => handleAddToCart(false)}
                                >
                                    <HiOutlineShoppingBag className="mr-2 h-5 w-5" />
                                    {isOutOfStock ? 'Sin Existencias' : 'Añadir al Carrito'}
                                </Button>
                            </div>

                            <Button
                                color="dark"
                                size="lg"
                                className="w-full"
                                disabled={isOutOfStock}
                                onClick={() => handleAddToCart(true)}
                            >
                                <span className="flex items-center justify-center gap-2 font-bold">
                                    Comprar Ahora Directamente
                                    <HiArrowRight className="w-5 h-5" />
                                </span>
                            </Button>
                        </div>

                        {/* Trust Badges */}
                        <div className="grid grid-cols-3 gap-3 pt-4 border-t dark:border-gray-800 text-center text-xs text-gray-500">
                            <div className="flex flex-col items-center gap-1">
                                <HiTruck className="w-5 h-5 text-blue-500" />
                                <span>Despacho Rápido</span>
                            </div>
                            <div className="flex flex-col items-center gap-1">
                                <HiLockClosed className="w-5 h-5 text-green-500" />
                                <span>Compra Segura</span>
                            </div>
                            <div className="flex flex-col items-center gap-1">
                                <HiShieldCheck className="w-5 h-5 text-purple-500" />
                                <span>Garantía Oficial</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Tabs: Description, Specs & Reviews */}
                <Card className="shadow-sm rounded-3xl" id="reviews-section">
                    <Tabs aria-label="Product tabs" variant="underline">
                        {/* Tab 1: Description */}
                        <TabItem active title="Descripción Detallada">
                            <div className="py-4 text-sm text-gray-700 dark:text-gray-300 leading-relaxed space-y-4">
                                {product.description ? (
                                    <div className="whitespace-pre-line">{product.description}</div>
                                ) : (
                                    <p className="text-gray-400 italic">
                                        No hay descripción adicional disponible para este producto.
                                    </p>
                                )}
                            </div>
                        </TabItem>

                        {/* Tab 2: Specs */}
                        {product.specifications && Object.keys(product.specifications).length > 0 && (
                            <TabItem title="Especificaciones Técnicas">
                                <div className="py-4">
                                    <div className="border dark:border-gray-800 rounded-2xl overflow-hidden divide-y dark:divide-gray-800 text-sm">
                                        {Object.entries(product.specifications).map(([key, val]) => (
                                            <div
                                                key={key}
                                                className="grid grid-cols-3 p-3 bg-white dark:bg-gray-900 even:bg-gray-50 dark:even:bg-gray-800/50"
                                            >
                                                <span className="font-semibold text-gray-600 dark:text-gray-400">
                                                    {key}
                                                </span>
                                                <span className="col-span-2 text-gray-900 dark:text-white">
                                                    {val}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </TabItem>
                        )}

                        {/* Tab 3: Reviews & Ratings */}
                        <TabItem title={`Opiniones (${reviews_summary.total_reviews})`}>
                            <div className="py-6 space-y-8">
                                {/* Rating Breakdown KPI Card */}
                                <div className="p-6 bg-gray-50 dark:bg-gray-800/60 rounded-2xl grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                                    {/* Score */}
                                    <div className="text-center md:border-r dark:border-gray-700 md:pr-6 space-y-2">
                                        <div className="text-5xl font-black text-gray-900 dark:text-white">
                                            {reviews_summary.avg_rating.toFixed(1)}
                                        </div>
                                        <div className="flex justify-center text-amber-400">
                                            {[1, 2, 3, 4, 5].map((star) => (
                                                <HiStar
                                                    key={star}
                                                    className={`w-5 h-5 ${
                                                        star <= Math.round(reviews_summary.avg_rating)
                                                            ? 'text-amber-400'
                                                            : 'text-gray-300 dark:text-gray-700'
                                                    }`}
                                                />
                                            ))}
                                        </div>
                                        <p className="text-xs text-gray-500">
                                            Basado en {reviews_summary.total_reviews} calificaciones
                                        </p>
                                    </div>

                                    {/* Bars */}
                                    <div className="space-y-1.5">
                                        {[5, 4, 3, 2, 1].map((stars) => {
                                            const count = reviews_summary.rating_breakdown[stars as keyof typeof reviews_summary.rating_breakdown] || 0;
                                            const pct =
                                                reviews_summary.total_reviews > 0
                                                    ? Math.round((count / reviews_summary.total_reviews) * 100)
                                                    : 0;

                                            return (
                                                <div key={stars} className="flex items-center gap-2 text-xs">
                                                    <span className="w-6 font-bold">{stars}★</span>
                                                    <div className="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                                        <div
                                                            className="bg-amber-400 h-2 rounded-full"
                                                            style={{ width: `${pct}%` }}
                                                        />
                                                    </div>
                                                    <span className="w-8 text-right text-gray-400">{count}</span>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {/* Action CTA */}
                                    <div className="text-center space-y-3">
                                        <h4 className="text-sm font-bold text-gray-900 dark:text-white">
                                            ¿Has probado este producto?
                                        </h4>
                                        <p className="text-xs text-gray-500 max-w-xs mx-auto">
                                            Comparte tu experiencia con otros compradores.
                                        </p>
                                        <Button
                                            color="blue"
                                            size="sm"
                                            className="mx-auto"
                                            onClick={() => setIsReviewModalOpen(true)}
                                        >
                                            <HiStar className="mr-1.5 h-4 w-4" />
                                            Escribir una Opinión
                                        </Button>
                                    </div>
                                </div>

                                {/* Approved Reviews List */}
                                <div className="space-y-4">
                                    <h3 className="text-base font-bold text-gray-900 dark:text-white">
                                        Opiniones de Clientes ({reviews.length})
                                    </h3>

                                    {reviews.length > 0 ? (
                                        <div className="divide-y dark:divide-gray-800">
                                            {reviews.map((rev) => (
                                                <div key={rev.id} className="py-4 space-y-2">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center gap-2">
                                                            <div className="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 font-bold flex items-center justify-center text-xs">
                                                                {rev.author_name.charAt(0).toUpperCase()}
                                                            </div>
                                                            <div>
                                                                <span className="text-xs font-bold text-gray-900 dark:text-white">
                                                                    {rev.author_name}
                                                                </span>
                                                                {rev.is_verified && (
                                                                    <Badge color="success" size="xs" className="inline-block ml-2">
                                                                        Comprador Verificado
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <span className="text-xs text-gray-400">
                                                            {rev.created_at}
                                                        </span>
                                                    </div>

                                                    {/* Rating Stars */}
                                                    <div className="flex text-amber-400">
                                                        {[1, 2, 3, 4, 5].map((s) => (
                                                            <HiStar
                                                                key={s}
                                                                className={`w-3.5 h-3.5 ${
                                                                    s <= rev.rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-700'
                                                                }`}
                                                            />
                                                        ))}
                                                    </div>

                                                    {rev.title && (
                                                        <h5 className="text-sm font-bold text-gray-900 dark:text-white">
                                                            {rev.title}
                                                        </h5>
                                                    )}

                                                    <p className="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                                        {rev.comment}
                                                    </p>

                                                    {/* Official Store Response */}
                                                    {rev.response && (
                                                        <div className="mt-3 p-3 bg-blue-50/70 dark:bg-blue-950/40 border-l-4 border-blue-500 rounded-r-xl space-y-1 text-xs">
                                                            <div className="flex items-center gap-1.5 font-bold text-blue-800 dark:text-blue-300">
                                                                <FaReply className="w-3 h-3 rotate-180" />
                                                                <span>Respuesta oficial de {store_settings?.store_name || 'la tienda'}</span>
                                                                {rev.responded_at && (
                                                                    <span className="text-gray-400 font-normal">({rev.responded_at})</span>
                                                                )}
                                                            </div>
                                                            <p className="text-gray-700 dark:text-gray-300 italic">
                                                                {rev.response}
                                                            </p>
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-8 text-xs text-gray-400 italic">
                                            Aún no hay opiniones aprobadas para este producto. ¡Sé el primero en calificarlo!
                                        </div>
                                    )}
                                </div>
                            </div>
                        </TabItem>
                    </Tabs>
                </Card>

                {/* Related Products Carousel */}
                {related_products.length > 0 && (
                    <section className="space-y-6 pt-6 border-t dark:border-gray-800">
                        <div className="flex justify-between items-end">
                            <div>
                                <h2 className="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                                    Productos Relacionados
                                </h2>
                                <p className="text-xs text-gray-500 mt-0.5">
                                    Otros artículos que podrían interesarte
                                </p>
                            </div>
                            <a
                                href="/catalog"
                                className="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                            >
                                Ver todos <HiArrowRight className="w-3.5 h-3.5" />
                            </a>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                            {related_products.map((relProduct) => (
                                <ProductCard key={relProduct.id} product={relProduct} />
                            ))}
                        </div>
                    </section>
                )}

                {/* Review Submission Modal */}
                <Modal
                    show={isReviewModalOpen}
                    onClose={() => setIsReviewModalOpen(false)}
                    size="lg"
                >
                    <ModalHeader>Calificar Producto</ModalHeader>
                    <ModalBody>
                        <form onSubmit={handleReviewSubmit} className="space-y-4">
                            {/* Stars Rating Selector */}
                            <div className="space-y-1 text-center py-2">
                                <Label className="text-xs font-bold uppercase text-gray-500 block">
                                    Tu Calificación:
                                </Label>
                                <div className="flex justify-center items-center gap-2">
                                    {[1, 2, 3, 4, 5].map((star) => (
                                        <button
                                            key={star}
                                            type="button"
                                            onMouseEnter={() => setHoverRating(star)}
                                            onMouseLeave={() => setHoverRating(0)}
                                            onClick={() => setReviewRating(star)}
                                            className="p-1 text-2xl transition-transform hover:scale-125 focus:outline-none"
                                        >
                                            <HiStar
                                                className={`w-8 h-8 ${
                                                    star <= (hoverRating || reviewRating)
                                                        ? 'text-amber-400'
                                                        : 'text-gray-200 dark:text-gray-700'
                                                }`}
                                            />
                                        </button>
                                    ))}
                                </div>
                                <span className="text-xs text-gray-500 font-semibold">
                                    {reviewRating} de 5 estrellas
                                </span>
                            </div>

                            <div>
                                <Label htmlFor="review_title_input">Título de la Opinión (Opcional)</Label>
                                <TextInput
                                    id="review_title_input"
                                    placeholder="Ej. ¡Excelente calidad y despacho rápido!"
                                    value={reviewTitle}
                                    onChange={(e) => setReviewTitle(e.target.value)}
                                    className="mt-1"
                                />
                            </div>

                            <div>
                                <Label htmlFor="review_comment_input">Tu Comentario (*)</Label>
                                <Textarea
                                    id="review_comment_input"
                                    placeholder="Describe tu experiencia con el producto, materiales, envío..."
                                    rows={4}
                                    required
                                    value={reviewComment}
                                    onChange={(e) => setReviewComment(e.target.value)}
                                    className="mt-1"
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="author_name_input">Tu Nombre (Público)</Label>
                                    <TextInput
                                        id="author_name_input"
                                        placeholder="Ej. Carlos M."
                                        value={authorName}
                                        onChange={(e) => setAuthorName(e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="author_email_input">Tu Correo Electrónico</Label>
                                    <TextInput
                                        id="author_email_input"
                                        type="email"
                                        placeholder="carlos@email.com"
                                        value={authorEmail}
                                        onChange={(e) => setAuthorEmail(e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                            </div>

                            <p className="text-[11px] text-gray-400">
                                🔒 Puedes opinar como invitado de forma libre. Tu reseña será revisada por el equipo antes de publicarse.
                            </p>

                            <div className="flex justify-end gap-2 pt-2 border-t dark:border-gray-700">
                                <Button
                                    color="gray"
                                    size="sm"
                                    onClick={() => setIsReviewModalOpen(false)}
                                    disabled={isSubmittingReview}
                                >
                                    Cancelar
                                </Button>
                                <Button
                                    color="blue"
                                    size="sm"
                                    type="submit"
                                    disabled={isSubmittingReview}
                                >
                                    {isSubmittingReview ? (
                                        <Spinner size="sm" className="mr-2" />
                                    ) : (
                                        <HiCheck className="mr-1.5 h-4 w-4" />
                                    )}
                                    Enviar Opinión
                                </Button>
                            </div>
                        </form>
                    </ModalBody>
                </Modal>
            </div>
        </>
    );
}

export default function TenantProductDetailPage(props: StorefrontProductDetailPageProps) {
    return (
        <StorefrontLayout
            domain={props.domain}
            title={props.product.name}
            storeSettings={props.store_settings}
            categories={props.categories}
            authUser={props.auth_user}
        >
            <ProductDetailPageContent {...props} />
        </StorefrontLayout>
    );
}
