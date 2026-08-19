export interface TenantSettingItem {
    id: string;
    key: string;
    value: string | null;
    typed_value: any;
    type: 'string' | 'boolean' | 'json' | 'integer' | 'float';
    group: 'general' | 'appearance' | 'social' | 'seo' | 'notifications';
    created_at: string | null;
    updated_at: string | null;
}

export interface StoreSettingsGrouped {
    general: {
        store_name: string;
        store_email: string;
        currency: string;
        contact_phone: string | null;
        address: string | null;
    };
    appearance: {
        logo_url: string | null;
        banner_url: string | null;
    };
    social: {
        facebook: string | null;
        instagram: string | null;
        whatsapp: string | null;
        twitter: string | null;
    };
    seo: {
        meta_title: string | null;
        meta_description: string | null;
        meta_keywords: string | null;
    };
}

export interface StoreSettingsFlat {
    store_name: string;
    store_email: string;
    currency: string;
    contact_phone: string | null;
    address: string | null;
    logo_url: string | null;
    banner_url: string | null;
    social_facebook: string | null;
    social_instagram: string | null;
    social_whatsapp: string | null;
    social_twitter: string | null;
    seo_title: string | null;
    seo_description: string | null;
    seo_keywords: string | null;
}

export interface StoreSettingsResponse {
    grouped: StoreSettingsGrouped;
    flat: StoreSettingsFlat;
}
