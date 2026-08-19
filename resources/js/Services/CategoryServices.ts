import { FormCategory } from '@/types/FormCategory';
import { Category } from '@/types/models/Category';
import { ApiResponse, Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosCategory = axios.create({
    baseURL: '/api-tenant/category/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

const CategoryServices = {
    filtrar: async (
        search: string | null = null,
        isActive: boolean | null = null,
        parentId: number | null = null,
        fechaDesdeUTC: string | null = null,
        fechaHastaUTC: string | null = null,
        prePage: number = 50,
        page: number = 1
    ): Promise<ApiResponse<Category[]>> => {
        try {
            const body = {
                search,
                is_active: isActive,
                parent_id: parentId,
                fechaDesdeUTC,
                fechaHastaUTC,
                prePage,
            };

            const respuesta: ApiResponse<Category[]> = await axiosCategory.post(`filter?page=${page}`, body);
            return respuesta;
        } catch (error) {
            return error as ApiResponse<Category[]>;
        }
    },

    tree: async (): Promise<ApiResponse<Category[]>> => {
        try {
            const respuesta: ApiResponse<Category[]> = await axiosCategory.get('tree');
            return respuesta;
        } catch (error) {
            return error as ApiResponse<Category[]>;
        }
    },

    consultById: async (id: number): Promise<ApiResponse<Category>> => {
        try {
            const respuesta: ApiResponse<Category> = await axiosCategory.get(`${id}`);
            return respuesta;
        } catch (error) {
            return error as ApiResponse<Category>;
        }
    },

    create: async (data: FormCategory): Promise<ApiResponse<Category>> => {
        try {
            const respuesta: ApiResponse<Category> = await axiosCategory.post('create', data);
            return respuesta;
        } catch (error) {
            return error as ApiResponse<Category>;
        }
    },

    edit: async (id: number, data: FormCategory): Promise<ApiResponse<Category>> => {
        try {
            const respuesta: ApiResponse<Category> = await axiosCategory.put(`${id}`, data);
            return respuesta;
        } catch (error) {
            return error as ApiResponse<Category>;
        }
    },

    delete: async (id: number): Promise<ApiResponse<void>> => {
        try {
            const respuesta: ApiResponse<void> = await axiosCategory.delete(`${id}`);
            return respuesta;
        } catch (error) {
            return error as ApiResponse<void>;
        }
    },

    syncCentral: async (): Promise<Data<{ synced_count: number; created_count: number; updated_count: number }>> => {
        try {
            const respuesta = await axiosCategory.post<Data<{ synced_count: number; created_count: number; updated_count: number }>>('sync-central');
            return respuesta.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al sincronizar con el catálogo central',
                    data: { synced_count: 0, created_count: 0, updated_count: 0 },
                    meta: [],
                }
            );
        }
    },
};

export default CategoryServices;
