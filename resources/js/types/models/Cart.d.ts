export interface CartItemAttribute {
    name: string;
    value: string;
}

export interface CartItem {
    id: string; // Unique key e.g. productId-variantId
    productId: string;
    variantId?: string;
    name: string;
    slug: string;
    sku?: string;
    image?: string;
    price: number;
    originalPrice?: number;
    quantity: number;
    maxStock: number;
    attributes?: Record<string, string>;
}

export interface AppliedCoupon {
    id?: string;
    code: string;
    type: 'percentage' | 'fixed' | 'fixed_amount';
    value: number;
    /** Importe calculado y validado por el backend. Es el que manda (hallazgo G3). */
    discountAmount: number;
    /**
     * Subtotal contra el que el backend valido el cupon. Si el carrito cambia, el
     * descuento deja de ser valido y hay que pedir una validacion nueva: el cliente no
     * puede reescalarlo por su cuenta sin saltarse los minimos y topes del backend.
     */
    validatedSubtotal?: number;
    description?: string;
}

export interface CartState {
    items: CartItem[];
    coupon: AppliedCoupon | null;
    isDrawerOpen: boolean;
}
