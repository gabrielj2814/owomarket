export interface Coupon {
    id: string;
    code: string;
    type: 'percentage' | 'fixed_amount';
    value: number;
    min_order_amount: number | null;
    usage_limit: number | null;
    usage_limit_per_customer: number | null;
    used_count: number;
    valid_from: string;
    valid_to: string;
    is_active: boolean;
    applicable_categories?: string[] | null;
    applicable_products?: string[] | null;
    created_at?: string;
    updated_at?: string;
}
