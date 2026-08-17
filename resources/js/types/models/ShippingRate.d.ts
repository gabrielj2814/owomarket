export interface ShippingRate {
    id: string;
    shipping_zone_id: string;
    name: string;
    type: 'flat' | 'free' | 'price_based' | 'weight_based';
    cost: number;
    min_value: number | null;
    max_value: number | null;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
}
