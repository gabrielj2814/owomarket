export interface FormShippingZone {
    name: string;
    countries?: string[] | null;
    states?: string[] | null;
    postal_codes?: string[] | null;
    priority?: number;
    is_active?: boolean;
}
