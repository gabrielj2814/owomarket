export interface CustomerAddress {
    id: string;
    type: 'shipping' | 'billing' | 'both' | 'other' | string;
    first_name: string;
    last_name: string;
    full_name: string;
    company: string | null;
    address_line_1: string;
    address_line_2: string | null;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    phone: string | null;
    is_default: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface Customer {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    birth_date: string | null;
    gender: 'male' | 'female' | 'other' | string | null;
    is_active: boolean;
    accepts_marketing: boolean;
    metadata: Record<string, any> | null;
    addresses: CustomerAddress[];
    created_at: string | null;
    updated_at: string | null;
}

export interface CustomerMetrics {
    total_customers: number;
    active_customers: number;
    marketing_subscribers: number;
    new_this_month: number;
}
