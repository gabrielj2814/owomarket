import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import { useCentralCart } from '@/contexts/CentralCartContext';
import CustomerPortalServices, { CustomerWishlistItemData } from '@/Services/CustomerPortalServices';
import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';
import {
    HiOutlineHeart,
    HiOutlineShoppingCart,
    HiOutlineTrash,
    HiOutlineBuildingStorefront,
} from 'react-icons/hi2';

export const CustomerWishlistPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const { addItem, setIsDrawerOpen } = useCentralCart();
    const [wishlist, setWishlist] = useState<CustomerWishlistItemData[]>([]);
    const [loading, setLoading] = useState(true);

    const loadWishlist = () => {
        if (!customer?.id) return;
        setLoading(true);
        CustomerPortalServices.getWishlist(customer.id)
            .then(res => {
                if (res?.data) {
                    setWishlist(res.data);
                }
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadWishlist();
    }, [customer?.id]);

    const handleRemove = async (item: CustomerWishlistItemData) => {
        if (!customer?.id) return;
        try {
            await CustomerPortalServices.toggleWishlist({
                customer_id: customer.id,
                product_id: item.product_id,
                tenant_id: item.tenant_id,
                product_name: item.product_name,
                product_price: item.product_price,
            });
            loadWishlist();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Error al actualizar lista de deseos.');
        }
    };

    const handleAddToCart = (item: CustomerWishlistItemData) => {
        addItem({
            tenant_id: item.tenant_id,
            tenant_name: item.tenant_id,
            product_id: item.product_id,
            product_name: item.product_name,
            slug: item.product_slug || item.product_id,
            price: item.product_price,
            image: item.product_image || undefined,
            quantity: 1,
        });
        setIsDrawerOpen(true);
    };

    return (
        <CustomerAccountLayout
            title="Mis Favoritos (Wishlist)"
            description="Guarda los artículos que más te gusten para comprarlos más adelante en un solo clic."
        >
            <Head title="Mis Favoritos - OwOMarket" />

            <div className="flex items-center justify-between mb-6">
                <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <HiOutlineHeart className="w-5 h-5 text-rose-600" />
                    Artículos Guardados ({wishlist.length})
                </h3>
            </div>

            {wishlist.length === 0 ? (
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center border border-gray-200/80 dark:border-gray-800/80">
                    <HiOutlineHeart className="w-12 h-12 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                    <h4 className="text-base font-bold text-gray-900 dark:text-white mb-1">
                        Tu lista de deseos está vacía
                    </h4>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-6">
                        Explora los productos de las tiendas y haz clic en el ícono de corazón para guardarlos aquí.
                    </p>
                    <Link
                        href="/marketplace"
                        className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition"
                    >
                        Explorar Catálogo
                    </Link>
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    {wishlist.map(item => (
                        <div
                            key={item.id}
                            className="bg-white dark:bg-gray-900 rounded-3xl p-4 shadow-sm border border-gray-200/80 dark:border-gray-800/80 flex flex-col justify-between group"
                        >
                            <div>
                                {item.product_image ? (
                                    <div className="w-full h-40 rounded-2xl overflow-hidden mb-3 bg-gray-50 dark:bg-gray-800">
                                        <img
                                            src={item.product_image}
                                            alt={item.product_name}
                                            className="w-full h-full object-cover group-hover:scale-105 transition"
                                        />
                                    </div>
                                ) : (
                                    <div className="w-full h-40 rounded-2xl mb-3 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 flex items-center justify-center text-blue-600">
                                        <HiOutlineBuildingStorefront className="w-12 h-12 opacity-50" />
                                    </div>
                                )}

                                <span className="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider block mb-1">
                                    Tienda: {item.tenant_id}
                                </span>

                                <h4 className="text-xs font-black text-gray-900 dark:text-white line-clamp-2 mb-2">
                                    {item.product_name}
                                </h4>

                                <div className="mb-4">
                                    <CurrencyPriceDisplay priceUsd={item.product_price} size="md" showVes={true} showBcvLabel={false} />
                                </div>
                            </div>

                            <div className="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                                <button
                                    onClick={() => handleAddToCart(item)}
                                    className="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 flex items-center justify-center gap-1.5 transition"
                                >
                                    <HiOutlineShoppingCart className="w-4 h-4" />
                                    Al Carrito
                                </button>
                                <button
                                    onClick={() => handleRemove(item)}
                                    className="p-2 text-gray-400 hover:text-red-600 rounded-xl hover:bg-red-50 dark:hover:bg-red-950/40 transition"
                                    title="Eliminar de favoritos"
                                >
                                    <HiOutlineTrash className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </CustomerAccountLayout>
    );
};

export default CustomerWishlistPage;
