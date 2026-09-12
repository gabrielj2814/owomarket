import PortalActionFeedback, { PortalFeedback } from '@/components/ui/customer/PortalActionFeedback';
import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import { Button, Card } from 'flowbite-react';
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
    // Hallazgo N35: un error de red era indistinguible de «no tienes nada».
    const [loadError, setLoadError] = useState(false);
    // Hallazgo C2: el resultado de cada accion, en linea en vez de un alert().
    const [feedback, setFeedback] = useState<PortalFeedback | null>(null);

    const loadWishlist = () => {
        if (!customer?.id) return;
        setLoading(true);
        CustomerPortalServices.getWishlist(customer.id)
            .then(res => {
                if (res?.data) {
                    setWishlist(res.data);
                }
            })
            .catch(() => setLoadError(true))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadWishlist();
    }, [customer?.id]);

    const handleRemove = async (item: CustomerWishlistItemData) => {
        if (!customer?.id) return;
        setFeedback(null);
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
            setFeedback({ type: 'error', text: err.response?.data?.message || 'No se pudo actualizar tu lista de deseos.' });
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
            {loadError && <PortalLoadError />}
            <PortalActionFeedback feedback={feedback} />

            <Head title="Mis Favoritos - OwOMarket" />

            <div className="flex items-center justify-between mb-6">
                <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <HiOutlineHeart className="w-5 h-5 text-rose-600" />
                    Artículos Guardados ({wishlist.length})
                </h3>
            </div>

            {wishlist.length === 0 ? (
                <Card>
                    <div data-testid="wishlist-vacio" className="py-6 text-center">
                        <HiOutlineHeart className="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-700" />
                        <h4 className="mb-1 text-base font-bold text-gray-900 dark:text-white">
                            Tu lista de deseos está vacía
                        </h4>
                        <p className="mb-6 text-xs text-gray-500 dark:text-gray-400">
                            Explora los productos de las tiendas y haz clic en el ícono de corazón para guardarlos
                            aquí.
                        </p>
                        <Button as={Link} href="/marketplace" color="primary" size="sm" className="mx-auto w-fit">
                            Explorar Catálogo
                        </Button>
                    </div>
                </Card>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    {wishlist.map(item => (
                        <Card
                            key={item.id}
                            className="group"
                            /* En rejilla, el pie tiene que quedar abajo aunque los nombres de
                               producto ocupen distinto: el tema centra por defecto. */
                            theme={{ root: { children: 'flex h-full flex-col justify-between gap-3 p-4' } }}
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
                                <Button color="primary" size="xs" className="flex-1" onClick={() => handleAddToCart(item)}>
                                    <HiOutlineShoppingCart className="mr-1.5 h-4 w-4" />
                                    Al Carrito
                                </Button>
                                <button
                                    onClick={() => handleRemove(item)}
                                    className="rounded-xl p-2 text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40"
                                    title="Eliminar de favoritos"
                                >
                                    <HiOutlineTrash className="h-4 w-4" />
                                </button>
                            </div>
                        </Card>
                    ))}
                </div>
            )}
        </CustomerAccountLayout>
    );
};

export default CustomerWishlistPage;
