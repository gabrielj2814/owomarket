export interface ErrorsFormShippingZone {
    name?: string[];
    countries?: string[];
    states?: string[];
    postal_codes?: string[];
    priority?: string[];
    is_active?: string[];
    [key: string]: string[] | undefined;
}
