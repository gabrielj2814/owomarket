import {
    CreateManualRatePayload,
    CurrencyConversionResult,
    ExchangeRateActiveResponse,
    ExchangeRateHistoryResponse,
} from '@/types/models/ExchangeRate';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const api = axios.create({
    timeout: 15000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

/**
 * Obtiene la tasa de cambio activa vigente (USD -> VES).
 */
export const getActiveExchangeRate = async (): Promise<ExchangeRateActiveResponse> => {
    try {
        const response = await api.get<ExchangeRateActiveResponse>('/api/exchange-rate/current');
        return response.data;
    } catch (error: any) {
        // Fallback a ruta central si la primera falla
        const response = await api.get<ExchangeRateActiveResponse>('/api/central/exchange-rate/current');
        return response.data;
    }
};

/**
 * Convierte un monto entre USD y VES en base a la tasa activa.
 */
export const convertCurrency = async (
    amount: number,
    from: 'USD' | 'VES' = 'USD',
    to: 'USD' | 'VES' = 'VES'
): Promise<{ success: boolean; data: CurrencyConversionResult }> => {
    const response = await api.get<{ success: boolean; data: CurrencyConversionResult }>(
        `/api/exchange-rate/convert?amount=${amount}&from=${from}&to=${to}`
    );
    return response.data;
};

/**
 * Dispara la sincronización inmediata de la cotización oficial del portal BCV.
 */
export const syncBcvRate = async (): Promise<{ success: boolean; message: string; data: any }> => {
    const response = await api.post<{ success: boolean; message: string; data: any }>(
        '/admin/backoffice/exchange-rates/sync-bcv'
    );
    return response.data;
};

/**
 * Registra una tasa de cambio manual de contingencia.
 */
export const createManualRate = async (
    payload: CreateManualRatePayload
): Promise<{ success: boolean; message: string; data: any }> => {
    const response = await api.post<{ success: boolean; message: string; data: any }>(
        '/admin/backoffice/exchange-rates/manual',
        payload
    );
    return response.data;
};

/**
 * Consulta el historial paginado de tasas de cambio registradas.
 */
export const listRatesHistory = async (params: {
    page?: number;
    per_page?: number;
    source?: string;
    date_from?: string;
    date_to?: string;
}): Promise<ExchangeRateHistoryResponse> => {
    const query = new URLSearchParams();
    if (params.page) query.append('page', params.page.toString());
    if (params.per_page) query.append('per_page', params.per_page.toString());
    if (params.source) query.append('source', params.source);
    if (params.date_from) query.append('date_from', params.date_from);
    if (params.date_to) query.append('date_to', params.date_to);

    const response = await api.get<ExchangeRateHistoryResponse>(
        `/admin/backoffice/exchange-rates/history?${query.toString()}`
    );
    return response.data;
};
