import { ErrorsFormBrand } from '@/types/ErrorsFormBrand';
import { FormBrand } from '@/types/FormBrand';
import { Brand } from '@/types/models/Brand';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosBrand = axios.create({
    baseURL: '/api-tenant/brand/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

const BrandServices = {
    filtrar: async (
        search: string | null = null,
        isActive: boolean | null = null,
        prePage: number = 50,
        page: number = 1
    ): Promise<ApiResponse<Brand[]>> => {
        try {
            const body = {
                search,
                is_active: isActive,
                prePage,
                page,
            };

            const response = await axiosBrand.post<ApiResponse<Brand[]>>('filter', body);
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

    listActive: async (): Promise<ApiResponse<Brand[]>> => {
        try {
            const response = await axiosBrand.get<ApiResponse<Brand[]>>('active');
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

    consultById: async (id: number): Promise<ApiResponse<Brand>> => {
        try {
            const response = await axiosBrand.get<ApiResponse<Brand>>(`${id}`);
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

    create: async (data: FormBrand): Promise<ApiResponse<Brand, ErrorsFormBrand>> => {
        try {
            const response = await axiosBrand.post<ApiResponse<Brand, ErrorsFormBrand>>('create', data);
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

    update: async (id: number, data: FormBrand): Promise<ApiResponse<Brand, ErrorsFormBrand>> => {
        try {
            const response = await axiosBrand.put<ApiResponse<Brand, ErrorsFormBrand>>(`${id}`, data);
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

    delete: async (id: number): Promise<ApiResponse<null>> => {
        try {
            const response = await axiosBrand.delete<ApiResponse<null>>(`${id}`);
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
};

export default BrandServices;
