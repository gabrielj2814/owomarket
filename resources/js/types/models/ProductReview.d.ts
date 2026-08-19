import { Customer } from './Customer';
import { Product } from './Product';

export interface ProductReview {
    id: string;
    product_id: string;
    customer_id: string;
    order_id: string | null;
    rating: number;
    title: string | null;
    comment: string | null;
    response: string | null;
    responded_at: string | null;
    is_approved: boolean;
    is_verified: boolean;
    created_at: string | null;
    updated_at: string | null;
    product?: Product | null;
    customer?: Customer | null;
}

export interface ProductRatingSummary {
    product_id: string | null;
    total_reviews: number;
    average_rating: number;
    star_breakdown: Record<number, number>;
}
