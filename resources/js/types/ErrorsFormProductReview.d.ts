export interface ErrorsFormCreateReview {
    product_id?: string[];
    customer_id?: string[];
    rating?: string[];
    order_id?: string[];
    title?: string[];
    comment?: string[];
    is_approved?: string[];
    is_verified?: string[];
}

export interface ErrorsFormUpdateReview {
    rating?: string[];
    title?: string[];
    comment?: string[];
}

export interface ErrorsFormRespondReview {
    response?: string[];
}
