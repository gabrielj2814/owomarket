import React, { useState } from 'react';
import { useCart } from '@/contexts/CartContext';
import { StorefrontProduct } from '@/types/models/Storefront';
import { Badge, Button } from 'flowbite-react';
import { HiOutlineShoppingBag, HiStar } from 'react-icons/hi';

interface ProductCardProps {
    product: StorefrontProduct;
}

export default function ProductCard({ product }: ProductCardProps) {
    const { addItem, formatPrice } = useCart();
    const [isHovered, setIsHovered] = useState<boolean>(false);

    const discountPercentage =
        product.compare_price && product.compare_price > product.price
            ? Math.round(((product.compare_price - product.price) / product.compare_price) * 100)
            : 0;

    const isOutOfStock = product.quantity <= 0;

    const handleAddToCart = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (isOutOfStock) return;

        addItem({
            productId: product.id,
            name: product.name,
            slug: product.slug,
            sku: product.sku,
            image: product.image,
            price: product.price,
            originalPrice: product.compare_price,
            quantity: 1,
            maxStock: product.quantity,
        });
    };

    return (
        <div
            onMouseEnter={() => setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
            className="group relative bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between"
        >
            <div>
                {/* 1. Image Container */}
                <div className="relative w-full aspect-square bg-gray-50 dark:bg-gray-800 overflow-hidden">
                    {/* Discount & Brand Badges */}
                    <div className="absolute top-3 left-3 z-10 flex flex-col gap-1 items-start">
                        {discountPercentage > 0 && (
                            <span className="bg-red-600 text-white text-[11px] font-black px-2 py-0.5 rounded-full shadow-md">
                                -{discountPercentage}%
                            </span>
                        )}
                        {product.brand_name && (
                            <span className="bg-gray-900/80 backdrop-blur-sm text-white text-[10px] font-semibold px-2 py-0.5 rounded-md">
                                {product.brand_name}
                            </span>
                        )}
                    </div>

                    {/* Stock Status Badge */}
                    <div className="absolute top-3 right-3 z-10">
                        {isOutOfStock ? (
                            <span className="bg-gray-700/90 text-gray-300 text-[10px] font-semibold px-2 py-0.5 rounded-md">
                                Agotado
                            </span>
                        ) : (
                            <span className="bg-green-600/90 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md">
                                Disponible
                            </span>
                        )}
                    </div>

                    {/* Product Image */}
                    <a href={`/product/${product.slug}`} className="block w-full h-full">
                        {product.image ? (
                            <img
                                src={product.image}
                                alt={product.name}
                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                onError={(e) => {
                                    (e.target as HTMLImageElement).src =
                                        'https://via.placeholder.com/400x400?text=Producto';
                                }}
                            />
                        ) : (
                            <div className="w-full h-full flex items-center justify-center text-gray-300 dark:text-gray-600">
                                <HiOutlineShoppingBag className="w-16 h-16" />
                            </div>
                        )}
                    </a>
                </div>

                {/* 2. Content Info */}
                <div className="p-4 sm:p-5 space-y-2">
                    {/* Category Label */}
                    {product.category_name && (
                        <p className="text-[11px] uppercase tracking-wider text-blue-600 dark:text-blue-400 font-bold">
                            {product.category_name}
                        </p>
                    )}

                    {/* Product Title */}
                    <h3 className="text-sm sm:text-base font-semibold text-gray-900 dark:text-white line-clamp-2 hover:text-blue-600 transition-colors">
                        <a href={`/product/${product.slug}`}>{product.name}</a>
                    </h3>

                    {/* Rating Stars */}
                    <div className="flex items-center gap-1 text-xs">
                        <div className="flex items-center text-amber-400">
                            {[1, 2, 3, 4, 5].map((star) => (
                                <HiStar
                                    key={star}
                                    className={`w-3.5 h-3.5 ${
                                        star <= Math.round(product.rating || 5)
                                            ? 'text-amber-400'
                                            : 'text-gray-300 dark:text-gray-700'
                                    }`}
                                />
                            ))}
                        </div>
                        <span className="font-semibold text-gray-700 dark:text-gray-300 text-[11px]">
                            {Number(product.rating || 5.0).toFixed(1)}
                        </span>
                        {product.reviews_count !== undefined && product.reviews_count > 0 && (
                            <span className="text-gray-400 text-[11px]">
                                ({product.reviews_count})
                            </span>
                        )}
                    </div>
                </div>
            </div>

            {/* 3. Price & Quick Add Button */}
            <div className="p-4 sm:p-5 pt-0 space-y-3">
                {/* Price Display */}
                <div className="flex items-baseline gap-2">
                    <span className="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        {formatPrice(product.price)}
                    </span>
                    {product.compare_price && product.compare_price > product.price && (
                        <span className="text-xs sm:text-sm line-through text-gray-400">
                            {formatPrice(product.compare_price)}
                        </span>
                    )}
                </div>

                {/* Add to Cart Button */}
                <Button
                    color="blue"
                    size="sm"
                    className="w-full"
                    disabled={isOutOfStock}
                    onClick={handleAddToCart}
                >
                    <HiOutlineShoppingBag className="mr-2 h-4 w-4" />
                    {isOutOfStock ? 'Sin Existencias' : 'Añadir al Carrito'}
                </Button>
            </div>
        </div>
    );
}
