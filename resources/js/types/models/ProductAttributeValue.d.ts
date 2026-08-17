export interface ProductAttributeValue {
    id: string;
    product_attribute_id: string;
    value: string;
    color: string | null;
    image: string | null;
    position: number;
    created_at?: string;
    updated_at?: string;
}
