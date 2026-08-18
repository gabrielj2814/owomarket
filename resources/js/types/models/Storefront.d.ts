export interface StorefrontCategory {
    id: string;
    name: string;
    slug: string;
    image?: string;
    products_count?: number;
}

export interface StorefrontProduct {
    id: string;
    name: string;
    slug: string;
    sku?: string;
    price: number;
    compare_price?: number;
    quantity: number;
    is_featured?: boolean;
    is_visible?: boolean;
    image?: string;
    brand_name?: string;
    category_name?: string;
    category_slug?: string;
    rating?: number;
    reviews_count?: number;
}

export interface StorefrontHomePageProps {
    domain: string;
    store_settings: {
        store_name?: string;
        store_email?: string;
        currency?: string;
        contact_phone?: string;
        address?: string;
        logo_url?: string;
        banner_url?: string;
        social_facebook?: string;
        social_instagram?: string;
        social_whatsapp?: string;
        social_twitter?: string;
        seo_title?: string;
        seo_description?: string;
        seo_keywords?: string;
    };
    categories: StorefrontCategory[];
    featured_products: StorefrontProduct[];
    new_products: StorefrontProduct[];
    auth_user?: {
        id: string;
        name: string;
        email: string;
    } | null;
}
