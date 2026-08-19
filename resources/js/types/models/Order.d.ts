import { Customer } from './Customer';

export interface OrderItem {
    id: string;
    order_id?: string;
    product_id: string;
    product_variant_id?: string | null;
    product_name: string;
    sku: string;
    price: number;
    quantity: number;
    attributes?: Record<string, any> | null;
    total: number;
}

export type OrderStatusType =
    | 'pending'
    | 'confirmed'
    | 'processing'
    | 'shipped'
    | 'delivered'
    | 'cancelled'
    | 'refunded';

export type PaymentStatusType = 'pending' | 'paid' | 'failed' | 'refunded';

export interface Order {
    id: string;
    order_number: string;
    customer_id: string;
    customer?: Customer | null;
    status: OrderStatusType;
    subtotal: number;
    tax_amount: number;
    shipping_amount: number;
    discount_amount: number;
    total: number;
    currency: string;
    payment_method: string;
    payment_status: PaymentStatusType;
    shipping_method?: string | null;
    notes?: string | null;
    customer_note?: string | null;
    confirmed_at?: string | null;
    cancelled_at?: string | null;
    shipped_at?: string | null;
    delivered_at?: string | null;
    metadata?: Record<string, any> | null;
    items: OrderItem[];
    created_at?: string | null;
    updated_at?: string | null;
}

export interface OrderMetrics {
    total_orders: number;
    pending_orders: number;
    processing_orders: number;
    completed_orders: number;
    total_sales_amount: number;
    average_order_value: number;
}
