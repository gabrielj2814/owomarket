export type RateSource = 'BCV_SCRAPING' | 'MANUAL_ADMIN' | 'API_FALLBACK';

export interface ExchangeRateItem {
    id: string;
    base_currency: string;
    target_currency: string;
    rate: number;
    formatted_rate?: string;
    source: RateSource;
    rate_date: string;
    is_active: boolean;
    metadata?: Record<string, any> | null;
    created_at?: string;
    updated_at?: string;
}

export interface ExchangeRateActiveResponse {
    success: boolean;
    data: ExchangeRateItem;
    message?: string;
}

export interface CurrencyConversionResult {
    amount_usd: number;
    amount_ves: number;
    rate: number;
    rate_date: string;
    source: string;
}

export interface ExchangeRateHistoryResponse {
    success: boolean;
    data: ExchangeRateItem[];
    meta: {
        total: number;
        current_page: number;
        per_page: number;
        last_page: number;
    };
}

export interface CreateManualRatePayload {
    rate: number;
    rate_date?: string;
    note?: string;
}
