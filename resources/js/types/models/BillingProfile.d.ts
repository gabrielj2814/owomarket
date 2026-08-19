export interface BillingAddressData {
    address_line_1: string;
    address_line_2?: string | null;
    city: string;
    state: string;
    postal_code: string;
    country: string;
}

export interface BillingProfile {
    id: string;
    legal_name: string;
    tax_id: string;
    billing_email: string;
    phone?: string | null;
    address: BillingAddressData;
    invoice_prefix: string;
    next_invoice_number: number;
    invoice_footer_notes?: string | null;
    logo_path?: string | null;
    metadata?: Record<string, any> | null;
    created_at?: string;
    updated_at?: string;
}
