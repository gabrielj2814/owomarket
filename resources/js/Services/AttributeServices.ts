import { ErrorsFormAttribute } from '@/types/ErrorsFormAttribute';
import { FormAttribute, FormAttributeValue } from '@/types/FormAttribute';
import { ProductAttribute } from '@/types/models/ProductAttribute';
import { ProductAttributeValue } from '@/types/models/ProductAttributeValue';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosAttribute = axios.create({
    baseURL: '/api-tenant/attribute/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

const AttributeServices = {
    filtrar: async (
        search: string | null = null,
        type: string | null = null,
        isFilterable: boolean | null = null,
        isVisible: boolean | null = null,
        prePage: number = 10,
        page: number = 1,
        sortBy: string = 'position',
        sortDirection: string = 'asc'
    ): Promise<ApiResponse<ProductAttribute[]>> => {
        try {
            const body = {
                search,
                type,
                is_filterable: isFilterable,
                is_visible: isVisible,
                prePage,
                page,
                sortBy,
                sortDirection,
            };

            const response = await axiosAttribute.post<ApiResponse<ProductAttribute[]>>('filter', body);
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

    listWithValues: async (): Promise<ApiResponse<ProductAttribute[]>> => {
        try {
            const response = await axiosAttribute.get<ApiResponse<ProductAttribute[]>>('with-values');
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

    consultById: async (id: string): Promise<ApiResponse<ProductAttribute>> => {
        try {
            const response = await axiosAttribute.get<ApiResponse<ProductAttribute>>(`${id}`);
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

    create: async (data: FormAttribute): Promise<ApiResponse<ProductAttribute, ErrorsFormAttribute>> => {
        try {
            const response = await axiosAttribute.post<ApiResponse<ProductAttribute, ErrorsFormAttribute>>('create', data);
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

    update: async (id: string, data: FormAttribute): Promise<ApiResponse<ProductAttribute, ErrorsFormAttribute>> => {
        try {
            const response = await axiosAttribute.put<ApiResponse<ProductAttribute, ErrorsFormAttribute>>(`${id}`, data);
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

    delete: async (id: string): Promise<ApiResponse<null>> => {
        try {
            const response = await axiosAttribute.delete<ApiResponse<null>>(`${id}`);
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

    createValue: async (attributeId: string, data: FormAttributeValue): Promise<ApiResponse<ProductAttributeValue>> => {
        try {
            const response = await axiosAttribute.post<ApiResponse<ProductAttributeValue>>(`${attributeId}/values`, data);
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

    deleteValue: async (valueId: string): Promise<ApiResponse<null>> => {
        try {
            const response = await axiosAttribute.delete<ApiResponse<null>>(`values/${valueId}`);
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

export default AttributeServices;
