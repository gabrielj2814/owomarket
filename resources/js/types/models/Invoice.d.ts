import { BillingAddressData } from './BillingProfile';

export interface InvoiceItem {
    id: string;
    product_id?: string | null;
    product_variant_id?: string | null;
    description: string;
    sku?: string | null;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    tax_amount: number;
    discount_amount: number;
    subtotal: number;
    total: number;
}

export interface IssuerSnapshot {
    legal_name: string;
    tax_id: string;
    billing_email: string;
    phone?: string | null;
    address: BillingAddressData;
    invoice_prefix: string;
    invoice_footer_notes?: string | null;
    logo_path?: string | null;
}

export interface Invoice {
    id: string;
    order_id?: string | null;
    customer_id?: string | null;
    invoice_number: string;
    status: 'draft' | 'issued' | 'paid' | 'cancelled' | 'refunded';
    issue_date: string;
    due_date?: string | null;
    currency: string;
    subtotal: number;
    tax_amount: number;
    discount_amount: number;
    total: number;
    payment_method: string;
    payment_status: string;
    paid_at?: string | null;
    billing_customer_name: string;
    billing_customer_tax_id?: string | null;
    billing_customer_email: string;
    billing_customer_address: BillingAddressData;
    issuer_snapshot: IssuerSnapshot;
    items: InvoiceItem[];
    pdf_path?: string | null;
    notes?: string | null;
    metadata?: Record<string, any> | null;
    created_at?: string;
    updated_at?: string;
}

export interface InvoiceMetrics {
    total_billed: number;
    total_issued: number;
    total_paid: number;
    total_cancelled: number;
}
