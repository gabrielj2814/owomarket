export interface StorefrontCategory {
    id: string;
    name: string;
    slug: string;
    image?: string;
    products_count?: number;
}

export interface StorefrontBrand {
    id: string;
    name: string;
    slug: string;
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

export interface StoreSettingsMap {
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
}

export interface StorefrontHomePageProps {
    domain: string;
    store_settings: StoreSettingsMap;
    categories: StorefrontCategory[];
    featured_products: StorefrontProduct[];
    new_products: StorefrontProduct[];
    auth_user?: {
        id: string;
        name: string;
        email: string;
    } | null;
}

export interface StorefrontPaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface StorefrontCatalogPageProps {
    domain: string;
    store_settings: StoreSettingsMap;
    categories: StorefrontCategory[];
    brands: StorefrontBrand[];
    price_bounds: {
        min: number;
        max: number;
    };
    products: {
        data: StorefrontProduct[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        links: StorefrontPaginationLink[];
    };
    filters: {
        search?: string;
        category?: string;
        brand?: string;
        min_price?: string | number;
        max_price?: string | number;
        sort?: string;
        filter?: string;
    };
    auth_user?: {
        id: string;
        name: string;
        email: string;
    } | null;
}
