import { ProductImage } from './models/ProductImage';
import { ProductVariant } from './models/ProductVariant';

export interface FormProduct {
    id?: string;
    name: string;
    slug: string;
    sku: string;
    price: number | string;
    compare_price?: number | string | null;
    cost_price?: number | string | null;
    quantity: number | string;
    min_quantity?: number | string;
    max_quantity?: number | string;
    track_quantity?: boolean;
    is_visible?: boolean;
    is_published_central?: boolean;
    is_featured?: boolean;
    is_digital?: boolean;
    digital_product_url?: string | null;
    description?: string | null;
    short_description?: string | null;
    barcode?: string | null;
    weight?: number | string | null;
    height?: number | string | null;
    width?: number | string | null;
    length?: number | string | null;
    category_id?: number | null;
    brand_id?: number | null;
    published_at?: string | null;
    seo?: Record<string, any> | null;
    specifications?: Record<string, any> | null;
    metadata?: Record<string, any> | null;
    images?: ProductImage[];
    variants?: ProductVariant[];
}
