export interface ErrorsFormCoupon {
    code?: string[];
    type?: string[];
    value?: string[];
    valid_from?: string[];
    valid_to?: string[];
    min_order_amount?: string[];
    usage_limit?: string[];
    usage_limit_per_customer?: string[];
    is_active?: string[];
    applicable_categories?: string[];
    applicable_products?: string[];
    [key: string]: string[] | undefined;
}
