export interface FormUpdateStoreSettings {
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

export interface FormSaveSettingItem {
    key: string;
    value?: string;
    type?: 'string' | 'boolean' | 'json' | 'integer' | 'float';
    group?: 'general' | 'appearance' | 'social' | 'seo' | 'notifications';
}
