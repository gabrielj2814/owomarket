import { ErrorsFormProduct } from '@/types/ErrorsFormProduct';
import { FormProduct } from '@/types/FormProduct';
import { Product } from '@/types/models/Product';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosProduct = axios.create({
    baseURL: '/api-tenant/product/',
    timeout: 15000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface ProductFilterParams {
    search?: string | null;
    category_id?: number | null;
    brand_id?: number | null;
    min_price?: number | null;
    max_price?: number | null;
    is_visible?: boolean | null;
    is_featured?: boolean | null;
    is_digital?: boolean | null;
    in_stock?: boolean | null;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    page?: number;
    per_page?: number;
}

export interface UploadMediaResult {
    url: string;
    image_path: string;
    path: string;
    filename: string;
    alt_text?: string;
}

const ProductServices = {
    filtrar: async (params: ProductFilterParams = {}): Promise<ApiResponse<Product[]>> => {
        try {
            const response = await axiosProduct.post<ApiResponse<Product[]>>('filter', params);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión al filtrar productos',
                    data: [],
                }
            );
        }
    },

    consultById: async (id: string): Promise<ApiResponse<Product>> => {
        try {
            const response = await axiosProduct.get<ApiResponse<Product>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión al consultar el producto',
                    data: null as any,
                }
            );
        }
    },

    create: async (data: FormProduct): Promise<ApiResponse<Product, ErrorsFormProduct>> => {
        try {
            const response = await axiosProduct.post<ApiResponse<Product, ErrorsFormProduct>>('create', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión al crear el producto',
                    data: null as any,
                }
            );
        }
    },

    update: async (id: string, data: FormProduct): Promise<ApiResponse<Product, ErrorsFormProduct>> => {
        try {
            const response = await axiosProduct.put<ApiResponse<Product, ErrorsFormProduct>>(`${id}`, data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión al actualizar el producto',
                    data: null as any,
                }
            );
        }
    },

    delete: async (id: string): Promise<ApiResponse<null>> => {
        try {
            const response = await axiosProduct.delete<ApiResponse<null>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión al eliminar el producto',
                    data: null,
                }
            );
        }
    },

    toggleVisibility: async (id: string, isVisible?: boolean): Promise<ApiResponse<null>> => {
        try {
            const response = await axiosProduct.patch<ApiResponse<null>>(`${id}/toggle-visibility`, {
                is_visible: isVisible,
            });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión al cambiar la visibilidad',
                    data: null,
                }
            );
        }
    },

    updateStock: async (id: string, quantity: number): Promise<ApiResponse<null>> => {
        try {
            const response = await axiosProduct.patch<ApiResponse<null>>(`${id}/stock`, {
                quantity,
            });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión al actualizar el stock',
                    data: null,
                }
            );
        }
    },

    uploadImage: async (file: File, altText: string = ''): Promise<ApiResponse<UploadMediaResult>> => {
        try {
            const formData = new FormData();
            formData.append('file', file);
            if (altText) {
                formData.append('alt_text', altText);
            }

            const response = await axios.post<ApiResponse<UploadMediaResult>>(
                '/api-tenant/product/media/upload',
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-CSRF-TOKEN': getCSRFToken(),
                    },
                }
            );
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión al subir la imagen',
                    data: null as any,
                }
            );
        }
    },

    deleteImage: async (imagePath: string): Promise<ApiResponse<null>> => {
        try {
            const response = await axiosProduct.delete<ApiResponse<null>>('media/delete', {
                data: { image_path: imagePath },
            });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión al eliminar la imagen',
                    data: null,
                }
            );
        }
    },
};

export default ProductServices;
