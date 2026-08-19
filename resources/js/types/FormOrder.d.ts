export interface FormOrderItem {
    product_id: string;
    product_variant_id?: string | null;
    product_name: string;
    sku: string;
    price: number;
    quantity: number;
    attributes?: Record<string, any> | null;
}

export interface FormOrder {
    customer_id: string;
    payment_method: string;
    currency?: string;
    tax_amount?: number;
    shipping_amount?: number;
    discount_amount?: number;
    order_number?: string;
    shipping_method?: string;
    notes?: string;
    customer_note?: string;
    metadata?: Record<string, any>;
    items: FormOrderItem[];
}
