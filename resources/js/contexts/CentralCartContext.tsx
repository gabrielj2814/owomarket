import {
    isStoredCentralCartItem,
    readStoredArray,
    versionedCartKey,
} from '@/utils/cartStorage';
import CentralMarketplaceServices, {
    CentralRevalidatedCartLine,
} from '@/Services/CentralMarketplaceServices';
import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';

export interface CentralCartItem {
    id: string; // unique item key
    tenant_id: string;
    tenant_name: string;
    tenant_domain?: string | null;
    product_id: string;
    product_name: string;
    slug: string;
    price: number;
    quantity: number;
    image?: string | null;
    sku?: string | null;
    attributes?: Record<string, string>;
}

export interface StoreCartGroup {
    tenant_id: string;
    tenant_name: string;
    tenant_domain?: string | null;
    subtotal: number;
    items_count: number;
    items: CentralCartItem[];
}

interface CentralCartContextType {
    items: CentralCartItem[];
    addItem: (item: Omit<CentralCartItem, 'id'>) => void;
    removeItem: (itemId: string) => void;
    updateQuantity: (itemId: string, quantity: number) => void;
    clearCart: () => void;
    clearStoreItems: (tenantId: string) => void;
    getItemsByStore: () => StoreCartGroup[];
    getSubtotal: () => number;
    getItemCount: () => number;
    isDrawerOpen: boolean;
    setIsDrawerOpen: (open: boolean) => void;
    /** Hallazgo N31: contrasta el carrito con `central_products` y lo corrige. */
    revalidate: () => Promise<void>;
    revalidationNotices: string[];
    dismissRevalidationNotices: () => void;
}

const CentralCartContext = createContext<CentralCartContextType | undefined>(undefined);

// Hallazgo G12: clave versionada y lectura validada, igual que el carrito de tienda.
const STORAGE_KEY = versionedCartKey('owomarket_central_cart');

export const CentralCartProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [items, setItems] = useState<CentralCartItem[]>(() =>
        readStoredArray<CentralCartItem>(STORAGE_KEY, isStoredCentralCartItem)
    );

    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const [revalidationNotices, setRevalidationNotices] = useState<string[]>([]);

    /**
     * Hallazgo N31: el carrito central seguia con el precio y el stock del dia en que se
     * anadio cada producto. Desde la Fase 2.2 el catalogo central si se mantiene al dia,
     * asi que la diferencia con lo que el comprador ve es real; y desde la Fase 0.4 el
     * checkout resuelve los precios por su cuenta, asi que se la encontraba al pagar.
     *
     * Se avisa de cada cambio: corregir el precio en silencio seria tan malo como no
     * corregirlo.
     */
    const revalidate = useCallback(async () => {
        if (items.length === 0) return;

        const res = await CentralMarketplaceServices.revalidateCart(
            items.map(i => ({
                tenant_id: i.tenant_id,
                product_id: i.product_id,
                quantity: i.quantity,
                price: i.price,
            }))
        );

        if (res.code !== 200 || !res.data) return;

        const avisos: string[] = [];
        const porClave = new Map<string, CentralRevalidatedCartLine>(
            res.data.lines.map(l => [`${l.tenant_id}_${l.product_id}`, l] as const)
        );

        setItems(prev =>
            prev.flatMap(item => {
                const line = porClave.get(`${item.tenant_id}_${item.product_id}`);
                if (!line) return [item];

                if (!line.available) {
                    avisos.push(line.reason ?? `«${item.product_name}» ya no está disponible y se ha quitado.`);
                    return [];
                }

                if (line.price_changed && typeof line.price === 'number') {
                    const subio = line.price > item.price;
                    avisos.push(
                        `El precio de «${item.product_name}» ${subio ? 'subió' : 'bajó'} de $${item.price.toFixed(2)} a $${line.price.toFixed(2)}.`
                    );
                }

                if (line.quantity_reduced && typeof line.quantity === 'number') {
                    avisos.push(`Solo quedan ${line.quantity} unidades de «${item.product_name}»; se ajustó la cantidad.`);
                }

                return [
                    {
                        ...item,
                        price: typeof line.price === 'number' ? line.price : item.price,
                        quantity: typeof line.quantity === 'number' ? line.quantity : item.quantity,
                    },
                ];
            })
        );

        setRevalidationNotices(avisos);
    }, [items]);

    const dismissRevalidationNotices = () => setRevalidationNotices([]);

    useEffect(() => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
        } catch (e) {
            console.error('Error saving central cart to localStorage', e);
        }
    }, [items]);

    const addItem = (item: Omit<CentralCartItem, 'id'>) => {
        setItems(prevItems => {
            const existingIndex = prevItems.findIndex(
                i =>
                    i.tenant_id === item.tenant_id &&
                    i.product_id === item.product_id &&
                    JSON.stringify(i.attributes || {}) === JSON.stringify(item.attributes || {})
            );

            if (existingIndex > -1) {
                // Hallazgo G5: `updated[existingIndex].quantity += ...` mutaba el objeto
                // del estado anterior, no una copia. Con React 19 en StrictMode el updater
                // se invoca dos veces, asi que anadir 2 unidades dejaba 4 en el carrito. Y
                // como la referencia del item no cambiaba, los hijos memoizados no se
                // volvian a renderizar.
                return prevItems.map((existing, index) =>
                    index === existingIndex
                        ? { ...existing, quantity: existing.quantity + item.quantity }
                        : existing
                );
            }

            const newItem: CentralCartItem = {
                ...item,
                id: `${item.tenant_id}_${item.product_id}_${Date.now()}`,
            };
            return [...prevItems, newItem];
        });

        setIsDrawerOpen(true);
    };

    const removeItem = (itemId: string) => {
        setItems(prev => prev.filter(i => i.id !== itemId));
    };

    const updateQuantity = (itemId: string, quantity: number) => {
        if (quantity <= 0) {
            removeItem(itemId);
            return;
        }

        setItems(prev =>
            prev.map(i => (i.id === itemId ? { ...i, quantity } : i))
        );
    };

    const clearCart = () => {
        setItems([]);
    };

    const clearStoreItems = (tenantId: string) => {
        setItems(prev => prev.filter(i => i.tenant_id !== tenantId));
    };

    const getItemsByStore = (): StoreCartGroup[] => {
        const groups: Record<string, StoreCartGroup> = {};

        items.forEach(item => {
            if (!groups[item.tenant_id]) {
                groups[item.tenant_id] = {
                    tenant_id: item.tenant_id,
                    tenant_name: item.tenant_name || 'Tienda Asociada',
                    tenant_domain: item.tenant_domain,
                    subtotal: 0,
                    items_count: 0,
                    items: [],
                };
            }

            groups[item.tenant_id].items.push(item);
            groups[item.tenant_id].subtotal += item.price * item.quantity;
            groups[item.tenant_id].items_count += item.quantity;
        });

        return Object.values(groups);
    };

    const getSubtotal = (): number => {
        return items.reduce((sum, item) => sum + item.price * item.quantity, 0);
    };

    const getItemCount = (): number => {
        return items.reduce((sum, item) => sum + item.quantity, 0);
    };

    return (
        <CentralCartContext.Provider
            value={{
                items,
                addItem,
                removeItem,
                updateQuantity,
                clearCart,
                clearStoreItems,
                getItemsByStore,
                getSubtotal,
                getItemCount,
                isDrawerOpen,
                setIsDrawerOpen,
                revalidate,
                revalidationNotices,
                dismissRevalidationNotices,
            }}
        >
            {children}
        </CentralCartContext.Provider>
    );
};

export const useCentralCart = (): CentralCartContextType => {
    const context = useContext(CentralCartContext);
    if (!context) {
        throw new Error('useCentralCart must be used within a CentralCartProvider');
    }
    return context;
};
