export interface ProductVariant {
    id?: string | null;
    product_id?: string;
    sku: string;
    price: number;
    compare_price?: number | null;
    cost_price?: number | null;
    quantity: number;
    image?: string | null;
    weight?: number | null;
    attributes: Record<string, string>;
    attribute_value_ids?: string[];
    created_at?: string;
    updated_at?: string;
}
