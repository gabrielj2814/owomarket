import { ShippingRate } from './ShippingRate';

export interface ShippingZone {
    id: string;
    name: string;
    countries?: string[] | null;
    states?: string[] | null;
    postal_codes?: string[] | null;
    priority: number;
    is_active: boolean;
    rates?: ShippingRate[];
    created_at?: string;
    updated_at?: string;
}
