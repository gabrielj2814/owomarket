import { ErrorsFormBrand } from '@/types/ErrorsFormBrand';
import { FormBrand } from '@/types/FormBrand';
import { Brand } from '@/types/models/Brand';
import { Data } from '@/types/ResponseApi';
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
        page: number = 1,
    ): Promise<Data<Brand[]>> => {
        try {
            const body = {
                search,
                is_active: isActive,
                prePage,
                page,
            };

            const response = await axiosBrand.post<Data<Brand[]>>('filter', body);
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

    listActive: async (): Promise<Data<Brand[]>> => {
        try {
            const response = await axiosBrand.get<Data<Brand[]>>('active');
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

    consultById: async (id: number): Promise<Data<Brand>> => {
        try {
            const response = await axiosBrand.get<Data<Brand>>(`${id}`);
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

    create: async (data: FormBrand): Promise<Data<Brand, ErrorsFormBrand>> => {
        try {
            const response = await axiosBrand.post<Data<Brand, ErrorsFormBrand>>('create', data);
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

    update: async (id: number, data: FormBrand): Promise<Data<Brand, ErrorsFormBrand>> => {
        try {
            const response = await axiosBrand.put<Data<Brand, ErrorsFormBrand>>(`${id}`, data);
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

    delete: async (id: number): Promise<Data<null>> => {
        try {
            const response = await axiosBrand.delete<Data<null>>(`${id}`);
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

    syncCentral: async (): Promise<Data<{ synced_count: number; created_count: number; updated_count: number }>> => {
        try {
            const response = await axiosBrand.post<Data<{ synced_count: number; created_count: number; updated_count: number }>>('sync-central');
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al sincronizar marcas con el catálogo central',
                    data: { synced_count: 0, created_count: 0, updated_count: 0 },
                    meta: [],
                }
            );
        }
    },
};

export default BrandServices;
