export interface FormCreateReview {
    product_id: string;
    customer_id: string;
    rating: number;
    order_id?: string;
    title?: string;
    comment?: string;
    is_approved?: boolean;
    is_verified?: boolean;
}

export interface FormModerateReview {
    is_approved: boolean;
}

export interface FormRespondReview {
    response: string;
}

export interface FormUpdateReview {
    rating: number;
    title?: string;
    comment?: string;
}

export interface FilterReviewsParams {
    search?: string | null;
    product_id?: string | null;
    customer_id?: string | null;
    rating?: number | null;
    is_approved?: boolean | null;
    is_verified?: boolean | null;
    has_response?: boolean | null;
    page?: number;
    per_page?: number;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}
