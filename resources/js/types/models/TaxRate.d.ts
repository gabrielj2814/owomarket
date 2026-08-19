export interface TaxRate {
    id: string;
    name: string;
    rate: number;
    country: string | null;
    state: string | null;
    city: string | null;
    zip: string | null;
    priority: number;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
}
