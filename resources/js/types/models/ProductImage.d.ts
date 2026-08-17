export interface ProductImage {
    id?: string | null;
    product_id?: string;
    image_path: string;
    alt_text?: string | null;
    is_default: boolean;
    order: number;
    created_at?: string;
    updated_at?: string;
}
