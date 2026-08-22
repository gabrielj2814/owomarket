import { ErrorsFormTaxRate } from '@/types/ErrorsFormTaxRate';
import { FormTaxRate } from '@/types/FormTaxRate';
import { TaxRate } from '@/types/models/TaxRate';
import { Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosTax = axios.create({
    baseURL: '/api-tenant/tax/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface CalculateTaxPayload {
    subtotal: number;
    country?: string | null;
    state?: string | null;
    city?: string | null;
    zip?: string | null;
}

export interface CalculateTaxResponse {
    subtotal: number;
    total_tax: number;
    total_with_tax: number;
    applied_rates: Array<{
        id: string;
        name: string;
        rate: number;
        tax_amount: number;
    }>;
}

const TaxServices = {
    filtrar: async (
        search: string | null = null,
        country: string | null = null,
        state: string | null = null,
        isActive: boolean | null = null,
        prePage: number = 10,
        page: number = 1,
        sortBy: string = 'priority',
        sortDirection: string = 'asc',
    ): Promise<Data<TaxRate[]>> => {
        try {
            const body = {
                search,
                country,
                state,
                is_active: isActive,
                prePage,
                page,
                sortBy,
                sortDirection,
            };

            const response = await axiosTax.post<Data<TaxRate[]>>('filter', body);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: [],
                }
            );
        }
    },

    consultById: async (id: string): Promise<Data<TaxRate>> => {
        try {
            const response = await axiosTax.get<Data<TaxRate>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: null as any,
                }
            );
        }
    },

    create: async (data: FormTaxRate): Promise<Data<TaxRate, ErrorsFormTaxRate>> => {
        try {
            const response = await axiosTax.post<Data<TaxRate, ErrorsFormTaxRate>>('create', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: null as any,
                }
            );
        }
    },

    update: async (id: string, data: FormTaxRate): Promise<Data<TaxRate, ErrorsFormTaxRate>> => {
        try {
            const response = await axiosTax.put<Data<TaxRate, ErrorsFormTaxRate>>(`${id}`, data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: null as any,
                }
            );
        }
    },

    delete: async (id: string): Promise<Data<null>> => {
        try {
            const response = await axiosTax.delete<Data<null>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: null,
                }
            );
        }
    },

    calculate: async (data: CalculateTaxPayload): Promise<Data<CalculateTaxResponse>> => {
        try {
            const response = await axiosTax.post<Data<CalculateTaxResponse>>('calculate', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: null as any,
                }
            );
        }
    },
};

export default TaxServices;
