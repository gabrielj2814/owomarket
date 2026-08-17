export interface FormCoupon {
    code: string;
    type: 'percentage' | 'fixed_amount';
    value: number;
    valid_from: string;
    valid_to: string;
    min_order_amount?: number | null;
    usage_limit?: number | null;
    usage_limit_per_customer?: number | null;
    is_active?: boolean;
    applicable_categories?: string[] | null;
    applicable_products?: string[] | null;
}
