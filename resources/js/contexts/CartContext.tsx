import { AppliedCoupon, CartItem } from '@/types/models/Cart';
import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';

interface CartContextType {
    items: CartItem[];
    coupon: AppliedCoupon | null;
    isDrawerOpen: boolean;
    totalCount: number;
    subtotal: number;
    discountAmount: number;
    total: number;
    currency: string;
    addItem: (item: Omit<CartItem, 'id'>) => void;
    removeItem: (id: string) => void;
    updateQuantity: (id: string, quantity: number) => void;
    clearCart: () => void;
    applyCoupon: (coupon: AppliedCoupon) => void;
    removeCoupon: () => void;
    openDrawer: () => void;
    closeDrawer: () => void;
    toggleDrawer: () => void;
    formatPrice: (amount: number) => string;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

interface CartProviderProps {
    children: React.ReactNode;
    currency?: string;
    domain?: string;
}

export const CartProvider: React.FC<CartProviderProps> = ({ children, currency = 'USD', domain = 'default' }) => {
    const storageKey = useMemo(() => `owomarket_cart_${domain.replace(/[^a-zA-Z0-9_-]/g, '_')}`, [domain]);

    const [items, setItems] = useState<CartItem[]>(() => {
        if (typeof window === 'undefined') return [];
        try {
            const saved = localStorage.getItem(storageKey);
            return saved ? JSON.parse(saved) : [];
        } catch {
            return [];
        }
    });

    const [coupon, setCoupon] = useState<AppliedCoupon | null>(() => {
        if (typeof window === 'undefined') return null;
        try {
            const savedCoupon = localStorage.getItem(`${storageKey}_coupon`);
            return savedCoupon ? JSON.parse(savedCoupon) : null;
        } catch {
            return null;
        }
    });

    const [isDrawerOpen, setIsDrawerOpen] = useState<boolean>(false);

    // Persist items to localStorage
    useEffect(() => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(items));
        } catch (e) {
            console.error('Error saving cart to localStorage', e);
        }
    }, [items, storageKey]);

    // Persist coupon to localStorage
    useEffect(() => {
        try {
            if (coupon) {
                localStorage.setItem(`${storageKey}_coupon`, JSON.stringify(coupon));
            } else {
                localStorage.removeItem(`${storageKey}_coupon`);
            }
        } catch (e) {
            console.error('Error saving coupon to localStorage', e);
        }
    }, [coupon, storageKey]);

    const addItem = (item: Omit<CartItem, 'id'>) => {
        const uniqueId = item.variantId ? `${item.productId}-${item.variantId}` : `${item.productId}-base`;

        setItems((prevItems) => {
            const existingIndex = prevItems.findIndex((i) => i.id === uniqueId);
            if (existingIndex > -1) {
                const updated = [...prevItems];
                const currentQty = updated[existingIndex].quantity;
                const newQty = Math.min(currentQty + item.quantity, item.maxStock || 999);
                updated[existingIndex] = {
                    ...updated[existingIndex],
                    quantity: newQty,
                    price: item.price,
                    maxStock: item.maxStock,
                };
                return updated;
            } else {
                return [
                    ...prevItems,
                    {
                        ...item,
                        id: uniqueId,
                        quantity: Math.min(item.quantity, item.maxStock || 999),
                    },
                ];
            }
        });

        setIsDrawerOpen(true);
    };

    const removeItem = (id: string) => {
        setItems((prev) => prev.filter((item) => item.id !== id));
    };

    const updateQuantity = (id: string, quantity: number) => {
        if (quantity <= 0) {
            removeItem(id);
            return;
        }
        setItems((prev) =>
            prev.map((item) => {
                if (item.id === id) {
                    const validQty = Math.min(quantity, item.maxStock || 999);
                    return { ...item, quantity: validQty };
                }
                return item;
            }),
        );
    };

    const clearCart = () => {
        setItems([]);
        setCoupon(null);
    };

    const applyCoupon = (newCoupon: AppliedCoupon) => {
        setCoupon(newCoupon);
    };

    const removeCoupon = () => {
        setCoupon(null);
    };

    const openDrawer = () => setIsDrawerOpen(true);
    const closeDrawer = () => setIsDrawerOpen(false);
    const toggleDrawer = () => setIsDrawerOpen((prev) => !prev);

    const totalCount = useMemo(() => {
        return items.reduce((acc, item) => acc + item.quantity, 0);
    }, [items]);

    const subtotal = useMemo(() => {
        return items.reduce((acc, item) => acc + item.price * item.quantity, 0);
    }, [items]);

    /**
     * Hallazgo G3: aqui se recalculaba el descuento en el cliente y se descartaba el que
     * habia calculado el backend, que se guardaba en `AppliedCoupon.discountAmount` y no
     * se leia en ningun sitio. Ademas el `Math.round` redondeaba a unidades enteras: un
     * 10% sobre $45,50 son $4,55 en el backend y se mostraban $5,00.
     *
     * Ahora manda el importe del backend. Y si el carrito cambia despues de aplicar el
     * cupon, el descuento **no se reescala**: se descarta, porque reescalarlo por nuestra
     * cuenta se salta los minimos y topes que solo el backend valida.
     */
    const discountAmount = useMemo(() => {
        if (!coupon) return 0;
        if (coupon.validatedSubtotal !== undefined && coupon.validatedSubtotal !== subtotal) {
            return 0;
        }

        return Math.min(coupon.discountAmount, subtotal);
    }, [coupon, subtotal]);

    /**
     * Un cupon validado contra otro subtotal se retira solo, para que el comprador vea que
     * tiene que volver a aplicarlo en vez de creer que conserva un descuento que ya no es.
     */
    useEffect(() => {
        if (coupon && coupon.validatedSubtotal !== undefined && coupon.validatedSubtotal !== subtotal) {
            setCoupon(null);
        }
    }, [coupon, subtotal]);

    const total = useMemo(() => {
        return Math.max(0, subtotal - discountAmount);
    }, [subtotal, discountAmount]);

    const formatPrice = (amount: number) => {
        const safeAmount = Number(amount) || 0;
        if (currency === 'CLP' || currency === 'ARS' || currency === 'COP') {
            return `$ ${safeAmount.toLocaleString('es-CL')}`;
        }
        if (currency === 'EUR') {
            return `€ ${safeAmount.toFixed(2)}`;
        }
        return `$ ${safeAmount.toFixed(2)} ${currency}`;
    };

    const contextValue: CartContextType = {
        items,
        coupon,
        isDrawerOpen,
        totalCount,
        subtotal,
        discountAmount,
        total,
        currency,
        addItem,
        removeItem,
        updateQuantity,
        clearCart,
        applyCoupon,
        removeCoupon,
        openDrawer,
        closeDrawer,
        toggleDrawer,
        formatPrice,
    };

    return <CartContext.Provider value={contextValue}>{children}</CartContext.Provider>;
};

export const useCart = (): CartContextType => {
    const context = useContext(CartContext);
    if (!context) {
        throw new Error('useCart must be used within a CartProvider');
    }
    return context;
};

export default CartContext;
