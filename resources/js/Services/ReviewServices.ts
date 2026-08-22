import { ErrorsFormCreateReview, ErrorsFormRespondReview, ErrorsFormUpdateReview } from '@/types/ErrorsFormProductReview';
import { FilterReviewsParams, FormCreateReview, FormUpdateReview } from '@/types/FormProductReview';
import { ProductRatingSummary, ProductReview } from '@/types/models/ProductReview';
import { Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosReview = axios.create({
    baseURL: '/api-tenant/review/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface PaginatedReviewsData {
    data: ProductReview[];
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
}

const ReviewServices = {
    filtrar: async (params: FilterReviewsParams = {}): Promise<Data<PaginatedReviewsData>> => {
        try {
            const body = {
                search: params.search ?? null,
                product_id: params.product_id ?? null,
                customer_id: params.customer_id ?? null,
                rating: params.rating ?? null,
                is_approved: params.is_approved ?? null,
                is_verified: params.is_verified ?? null,
                has_response: params.has_response ?? null,
                per_page: params.per_page ?? 15,
                page: params.page ?? 1,
                sort_by: params.sort_by ?? 'created_at',
                sort_direction: params.sort_direction ?? 'desc',
            };

            const response = await axiosReview.post<Data<PaginatedReviewsData>>('filter', body);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al filtrar reseñas',
                    data: {
                        data: [],
                        total: 0,
                        current_page: 1,
                        per_page: 15,
                        last_page: 1,
                    },
                }
            );
        }
    },

    getSummary: async (productId?: string): Promise<Data<ProductRatingSummary>> => {
        try {
            const endpoint = productId ? `summary/${productId}` : 'summary';
            const response = await axiosReview.get<Data<ProductRatingSummary>>(endpoint);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar resumen de calificaciones',
                    data: {
                        product_id: productId ?? null,
                        total_reviews: 0,
                        average_rating: 0,
                        star_breakdown: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
                    },
                }
            );
        }
    },

    /**
     * Devuelve el sobre del backend, no la respuesta de axios. Ver hallazgo G2: tiparlo
     * como `ApiResponse` hacia que el consumidor desenvolviera una capa de mas.
     */
    create: async (data: FormCreateReview): Promise<Data<ProductReview, ErrorsFormCreateReview>> => {
        try {
            const response = await axiosReview.post<Data<ProductReview, ErrorsFormCreateReview>>('create', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al registrar la reseña',
                    data: null as any,
                }
            );
        }
    },

    consultById: async (id: string): Promise<Data<ProductReview>> => {
        try {
            const response = await axiosReview.get<Data<ProductReview>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar la reseña',
                    data: null as any,
                }
            );
        }
    },

    moderate: async (id: string, isApproved: boolean): Promise<Data<ProductReview>> => {
        try {
            const response = await axiosReview.post<Data<ProductReview>>(`${id}/moderate`, {
                is_approved: isApproved,
            });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al moderar la reseña',
                    data: null as any,
                }
            );
        }
    },

    respond: async (id: string, responseText: string): Promise<Data<ProductReview, ErrorsFormRespondReview>> => {
        try {
            const response = await axiosReview.post<Data<ProductReview, ErrorsFormRespondReview>>(`${id}/respond`, { response: responseText });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al responder la reseña',
                    data: null as any,
                }
            );
        }
    },

    update: async (id: string, data: FormUpdateReview): Promise<Data<ProductReview, ErrorsFormUpdateReview>> => {
        try {
            const response = await axiosReview.put<Data<ProductReview, ErrorsFormUpdateReview>>(`${id}`, data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al actualizar la reseña',
                    data: null as any,
                }
            );
        }
    },

    delete: async (id: string): Promise<Data<null>> => {
        try {
            const response = await axiosReview.delete<Data<null>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al eliminar la reseña',
                    data: null,
                }
            );
        }
    },
};

export default ReviewServices;
