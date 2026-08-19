export interface ErrorsFormDirectInvoice {
    customer_name?: string[];
    customer_email?: string[];
    customer_tax_id?: string[];
    customer_address_line_1?: string[];
    customer_city?: string[];
    customer_state?: string[];
    customer_postal_code?: string[];
    customer_country?: string[];
    items?: string[];
    payment_method?: string[];
    currency?: string[];
    [key: string]: string[] | undefined;
}
