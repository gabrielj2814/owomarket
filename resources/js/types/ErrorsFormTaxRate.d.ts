export interface ErrorsFormTaxRate {
    name?: string[];
    rate?: string[];
    country?: string[];
    state?: string[];
    city?: string[];
    zip?: string[];
    priority?: string[];
    is_active?: string[];
    [key: string]: string[] | undefined;
}
