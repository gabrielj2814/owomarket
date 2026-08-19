export interface FormShippingRate {
    name: string;
    type: 'flat' | 'free' | 'price_based' | 'weight_based';
    cost: number;
    min_value?: number | null;
    max_value?: number | null;
    is_active?: boolean;
}
