export interface ErrorsFormOrder {
    customer_id?: string[];
    payment_method?: string[];
    currency?: string[];
    tax_amount?: string[];
    shipping_amount?: string[];
    discount_amount?: string[];
    order_number?: string[];
    shipping_method?: string[];
    notes?: string[];
    customer_note?: string[];
    items?: string[];
    [key: string]: string[] | undefined;
}
