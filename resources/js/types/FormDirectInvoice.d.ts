export interface FormInvoiceItemRow {
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    discount_amount: number;
    sku?: string;
    product_id?: string;
}

export interface FormDirectInvoice {
    customer_name: string;
    customer_email: string;
    customer_tax_id?: string;
    customer_address_line_1: string;
    customer_address_line_2?: string;
    customer_city: string;
    customer_state: string;
    customer_postal_code: string;
    customer_country: string;
    items: FormInvoiceItemRow[];
    payment_method: string;
    payment_status: string;
    status: string;
    issue_date?: string;
    due_date?: string;
    currency: string;
    notes?: string;
}

export interface FilterInvoicesParams {
    search?: string;
    status?: string;
    payment_status?: string;
    payment_method?: string;
    date_from?: string;
    date_to?: string;
    min_total?: number;
    max_total?: number;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    page?: number;
    per_page?: number;
}
