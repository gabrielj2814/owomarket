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
    discountAmount: number;
    description?: string;
}

export interface CartState {
    items: CartItem[];
    coupon: AppliedCoupon | null;
    isDrawerOpen: boolean;
}
